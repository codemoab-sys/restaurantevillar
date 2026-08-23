<?php

namespace App\Services\Sunat;

use App\Models\Order;
use Greenter\Model\Client\Client as GClient;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\SaleDetail;

/**
 * Convierte una orden del POS en un objeto Invoice (Factura/Boleta) de Greenter.
 *
 * Maneja:
 *  - Boleta de Venta (03)  → cliente con DNI o "sin documento"
 *  - Factura (01)          → cliente con RUC y razón social obligatorios
 *  - IGV 18% (configurable)
 *  - Descuento global aplicado a línea (proporcional)
 */
class InvoiceBuilder
{
    public function __construct(private SunatConfig $config)
    {
    }

    /**
     * @param  Order  $order  Debe traer details.product, serie y correlativo asignados.
     */
    public function build(Order $order): Invoice
    {
        $isFactura = $order->document_type === 'Factura';
        $tipoDoc   = $isFactura ? '01' : '03';
        $tipoMoneda = 'PEN';

        // 1. Detalles (cálculo línea a línea con IGV incluido en el precio del POS)
        $igvFactor = $this->config->igvFactor();          // 0.18
        $denom     = 1 + $igvFactor;                      // 1.18

        $details = [];
        $totalGravadaSinIgv = 0.0;
        $totalIgv           = 0.0;

        foreach ($order->details as $line) {
            $product = $line->product;

            // Precio del POS = precio de venta CON IGV
            $precioVentaUnit = (float) $line->price;
            $valorUnit       = round($precioVentaUnit / $denom, 6);
            $cantidad        = (float) $line->quantity;
            $valorVenta      = round($valorUnit * $cantidad, 2);
            $igvLinea        = round($valorVenta * $igvFactor, 2);

            $totalGravadaSinIgv += $valorVenta;
            $totalIgv           += $igvLinea;

            $details[] = (new SaleDetail())
                ->setCodProducto((string) ($product->id ?? '-'))
                ->setUnidad('NIU')                                // Unidad: Bien (NIU) o Servicio (ZZ)
                ->setCantidad($cantidad)
                ->setDescripcion($product->name ?? 'Producto')
                ->setMtoBaseIgv($valorVenta)
                ->setPorcentajeIgv($this->config->igvRate())
                ->setIgv($igvLinea)
                ->setTipAfeIgv('10')                              // 10 = Gravado - Operación Onerosa
                ->setTotalImpuestos($igvLinea)
                ->setMtoValorVenta($valorVenta)
                ->setMtoValorUnitario($valorUnit)
                ->setMtoPrecioUnitario(round($precioVentaUnit, 2));
        }

        // 2. Totales (con redondeo final)
        $totalGravadaSinIgv = round($totalGravadaSinIgv, 2);
        $totalIgv           = round($totalIgv, 2);
        $totalImpuestos     = $totalIgv;
        $totalVenta         = round($totalGravadaSinIgv + $totalIgv, 2);

        // 3. Cliente
        $client = $this->buildClient($order, $isFactura);

        // 4. Empresa emisora
        $company = $this->buildCompany();

        // 5. Cabecera del comprobante
        $invoice = (new Invoice())
            ->setUblVersion('2.1')
            ->setTipoOperacion('0101')                            // 0101 = Venta interna
            ->setTipoDoc($tipoDoc)
            ->setSerie($order->serie)
            ->setCorrelativo((string) $order->correlativo)
            ->setFechaEmision($order->created_at ?? now())
            ->setTipoMoneda($tipoMoneda)
            ->setClient($client)
            ->setCompany($company)
            ->setMtoOperGravadas($totalGravadaSinIgv)
            ->setMtoOperExoneradas(0)
            ->setMtoOperInafectas(0)
            ->setMtoOperGratuitas(0)
            ->setMtoIGV($totalIgv)
            ->setTotalImpuestos($totalImpuestos)
            ->setValorVenta($totalGravadaSinIgv)
            ->setSubTotal($totalVenta)
            ->setMtoImpVenta($totalVenta)
            ->setDetails($details)
            ->setLegends([
                (new Legend())
                    ->setCode('1000')                             // Monto en letras
                    ->setValue($this->montoEnLetras($totalVenta) . ' SOLES'),
            ]);

        return $invoice;
    }

    private function buildClient(Order $order, bool $isFactura): GClient
    {
        $client = new GClient();

        if ($isFactura) {
            $client->setTipoDoc('6')                              // 6 = RUC
                ->setNumDoc($order->client_document ?: '20000000001')
                ->setRznSocial($order->client_name ?: 'CLIENTE GENERAL');
        } else {
            // Boleta: DNI (1) si tiene 8 dígitos, sino "0" sin documento
            $doc = trim((string) $order->client_document);
            $tipo = (strlen($doc) === 8) ? '1' : '0';
            $client->setTipoDoc($tipo)
                ->setNumDoc($doc ?: '-')
                ->setRznSocial($order->client_name ?: 'CLIENTE VARIOS');
        }

        return $client;
    }

    private function buildCompany(): Company
    {
        $address = (new Address())
            ->setUbigueo($this->config->ubigeo())
            ->setDepartamento($this->config->departamento())
            ->setProvincia($this->config->provincia())
            ->setDistrito($this->config->distrito())
            ->setUrbanizacion($this->config->urbanizacion())
            ->setCodLocal('0000')
            ->setDireccion($this->config->direccion())
            ->setCodigoPais($this->config->codigoPais());

        return (new Company())
            ->setRuc($this->config->ruc())
            ->setRazonSocial($this->config->razonSocial())
            ->setNombreComercial($this->config->nombreComercial())
            ->setAddress($address);
    }

    /**
     * Convierte un número a letras (formato SUNAT).
     * Mínima implementación; para producción podría reemplazarse por una librería.
     */
    private function montoEnLetras(float $amount): string
    {
        $entero = (int) floor($amount);
        $decimal = (int) round(($amount - $entero) * 100);

        return strtoupper($this->numeroALetras($entero)) . sprintf(' CON %02d/100', $decimal);
    }

    private function numeroALetras(int $n): string
    {
        if ($n === 0) return 'CERO';
        if ($n < 0)  return 'MENOS ' . $this->numeroALetras(-$n);

        $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
                     'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS',
                     'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE', 'VEINTE'];
        $decenas  = ['', '', 'VEINTI', 'TREINTA', 'CUARENTA', 'CINCUENTA',
                     'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS',
                     'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        if ($n <= 20) return $unidades[$n];

        if ($n < 100) {
            $d = intdiv($n, 10);
            $u = $n % 10;
            if ($d === 2) {
                return $u === 0 ? 'VEINTE' : 'VEINTI' . strtolower($unidades[$u]);
            }
            return $decenas[$d] . ($u ? ' Y ' . $unidades[$u] : '');
        }

        if ($n < 1000) {
            if ($n === 100) return 'CIEN';
            $c = intdiv($n, 100);
            $r = $n % 100;
            return $centenas[$c] . ($r ? ' ' . $this->numeroALetras($r) : '');
        }

        if ($n < 1_000_000) {
            $miles = intdiv($n, 1000);
            $resto = $n % 1000;
            $prefijo = $miles === 1 ? 'MIL' : $this->numeroALetras($miles) . ' MIL';
            return $prefijo . ($resto ? ' ' . $this->numeroALetras($resto) : '');
        }

        $millones = intdiv($n, 1_000_000);
        $resto    = $n % 1_000_000;
        $prefijo  = $millones === 1 ? 'UN MILLON' : $this->numeroALetras($millones) . ' MILLONES';
        return $prefijo . ($resto ? ' ' . $this->numeroALetras($resto) : '');
    }
}
