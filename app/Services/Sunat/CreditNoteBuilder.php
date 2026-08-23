<?php

namespace App\Services\Sunat;

use App\Models\CreditNote;
use App\Models\Order;
use Greenter\Model\Client\Client as GClient;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\Document;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\Note;
use Greenter\Model\Sale\SaleDetail;

/**
 * Convierte una CreditNote (vinculada a una Order) en un objeto Note de Greenter (tipo 07).
 */
class CreditNoteBuilder
{
    public function __construct(private SunatConfig $config)
    {
    }

    public function build(CreditNote $cn): Note
    {
        $order = $cn->order()->with('details.product')->first();
        if (!$order) {
            throw new \RuntimeException('La nota de crédito no tiene una orden asociada.');
        }

        $isFactura = $order->document_type === 'Factura';
        $igvFactor = $this->config->igvFactor();
        $denom     = 1 + $igvFactor;

        // Detalles (replicamos los del comprobante afectado)
        $details = [];
        foreach ($order->details as $line) {
            $product         = $line->product;
            $precioVentaUnit = (float) $line->price;
            $valorUnit       = round($precioVentaUnit / $denom, 6);
            $cantidad        = (float) $line->quantity;
            $valorVenta      = round($valorUnit * $cantidad, 2);
            $igvLinea        = round($valorVenta * $igvFactor, 2);

            $details[] = (new SaleDetail())
                ->setCodProducto((string) ($product->id ?? '-'))
                ->setUnidad('NIU')
                ->setCantidad($cantidad)
                ->setDescripcion($product->name ?? 'Producto')
                ->setMtoBaseIgv($valorVenta)
                ->setPorcentajeIgv($this->config->igvRate())
                ->setIgv($igvLinea)
                ->setTipAfeIgv('10')
                ->setTotalImpuestos($igvLinea)
                ->setMtoValorVenta($valorVenta)
                ->setMtoValorUnitario($valorUnit)
                ->setMtoPrecioUnitario(round($precioVentaUnit, 2));
        }

        $client  = $this->buildClient($order, $isFactura);
        $company = $this->buildCompany();

        $note = (new Note())
            ->setUblVersion('2.1')
            ->setTipoDoc('07')                            // 07 = Nota de crédito
            ->setSerie($cn->serie)
            ->setCorrelativo((string) $cn->correlativo)
            ->setFechaEmision($cn->created_at ?? now())
            ->setTipoMoneda('PEN')
            ->setClient($client)
            ->setCompany($company)
            ->setTipDocAfectado($isFactura ? '01' : '03') // Doc que se modifica
            ->setNumDocfectado($order->full_number)       // Ej: "F001-3"
            ->setCodMotivo($cn->reason_code)              // Catálogo 09
            ->setDesMotivo($cn->reason_description)
            ->setMtoOperGravadas((float) $cn->subtotal)
            ->setMtoIGV((float) $cn->igv)
            ->setTotalImpuestos((float) $cn->igv)
            ->setMtoImpVenta((float) $cn->total)
            ->setDetails($details)
            ->setLegends([
                (new Legend())
                    ->setCode('1000')
                    ->setValue($this->montoEnLetras((float) $cn->total) . ' SOLES'),
            ]);

        return $note;
    }

    private function buildClient(Order $order, bool $isFactura): GClient
    {
        $client = new GClient();

        if ($isFactura) {
            $client->setTipoDoc('6')
                ->setNumDoc($order->client_document ?: '20000000001')
                ->setRznSocial($order->client_name ?: 'CLIENTE GENERAL');
        } else {
            $doc  = trim((string) $order->client_document);
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

    private function montoEnLetras(float $amount): string
    {
        // Reusa el helper del InvoiceBuilder
        $ib = new InvoiceBuilder($this->config);
        $ref = new \ReflectionMethod($ib, 'montoEnLetras');
        $ref->setAccessible(true);
        return $ref->invoke($ib, $amount);
    }
}
