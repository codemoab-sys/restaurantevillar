<?php

namespace App\Services\Sunat;

use App\Models\CreditNote;
use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Servicio principal de facturación electrónica vía NubeFact (API REST JSON).
 *
 * Reemplaza al SunatService que usaba Greenter (SOAP/XML).
 * Envía JSON a la RUTA de NubeFact con autenticación TOKEN.
 *
 * Documentación: GUIA-FACTURACION-ELECTRONICA-NUBEFACT-DOC-API JSON V1.txt
 */
class NubeFactService
{
    private SunatConfig $config;
    private NubeFactInvoiceBuilder $invoiceBuilder;
    private NubeFactCreditNoteBuilder $creditNoteBuilder;

    public function __construct(?SunatConfig $config = null)
    {
        $this->config           = $config ?? new SunatConfig();
        $this->invoiceBuilder   = new NubeFactInvoiceBuilder($this->config);
        $this->creditNoteBuilder = new NubeFactCreditNoteBuilder($this->config);
    }

    // ═══════════════════════════════════════════════════════════════
    //  FACTURA / BOLETA
    // ═══════════════════════════════════════════════════════════════

    public function sendInvoice(Order $order): Order
    {
        if (!$order->serie || !$order->correlativo) {
            throw new \RuntimeException('La orden no tiene serie/correlativo asignados.');
        }

        if (!$this->config->isNubefactConfigured()) {
            throw new \RuntimeException('Configure RUTA, TOKEN y datos reales del emisor en Configuración SUNAT.');
        }

        $json = $this->invoiceBuilder->build($order);

        try {
            $response = $this->send($json);

            $order->sent_at = now();

            if (isset($response['errors'])) {
                // Error de validación de NubeFact
                $order->sunat_status      = 'REJECTED';
                $order->sunat_code        = $response['codigo'] ?? '400';
                $order->sunat_description = $response['errors'];
            } elseif (isset($response['aceptada_por_sunat'])) {
                // Respuesta válida de NubeFact
                $order->sunat_code        = $response['sunat_responsecode'] ?? null;
                $order->sunat_description = $response['sunat_description'] ?? '';

                if ($response['aceptada_por_sunat'] === true) {
                    $order->sunat_status = 'ACCEPTED';
                } else {
                    $order->sunat_status = 'OBSERVED';
                    $order->sunat_description = $response['sunat_description'] ?? 'Observada por SUNAT';
                }

                // Guardar enlace del PDF/XML/CDR si existen
                if (!empty($response['enlace_del_pdf'])) {
                    $order->pdf_path = $response['enlace_del_pdf'];
                } elseif (!empty($response['enlace'])) {
                    $order->pdf_path = rtrim($response['enlace'], '/') . '.pdf';
                }
                if (!empty($response['enlace_del_xml'])) {
                    $order->xml_path = $response['enlace_del_xml'];
                } elseif (!empty($response['enlace'])) {
                    $order->xml_path = rtrim($response['enlace'], '/') . '.xml';
                }
                if (!empty($response['enlace_del_cdr'])) {
                    $order->cdr_path = $response['enlace_del_cdr'];
                } elseif (!empty($response['enlace'])) {
                    $order->cdr_path = rtrim($response['enlace'], '/') . '.cdr';
                }

                // Hash y código QR
                $order->hash = $response['codigo_hash'] ?? null;
            }
        } catch (Exception $e) {
            Log::error('NubeFact sendInvoice error', [
                'order_id' => $order->id,
                'msg'      => $e->getMessage(),
            ]);
            $order->sunat_status      = 'ERROR';
            $order->sunat_code        = '500';
            $order->sunat_description = $e->getMessage();
        }

        $order->save();
        return $order;
    }

    // ═══════════════════════════════════════════════════════════════
    //  NOTA DE CRÉDITO
    // ═══════════════════════════════════════════════════════════════

    public function sendCreditNote(CreditNote $cn): CreditNote
    {
        if (!$cn->order || $cn->order->sunat_status !== 'ACCEPTED') {
            throw new \RuntimeException('Solo se puede enviar una nota de crédito para un comprobante aceptado por SUNAT.');
        }

        if (!$this->config->isNubefactConfigured()) {
            throw new \RuntimeException('Configure RUTA, TOKEN y datos reales del emisor en Configuración SUNAT.');
        }

        $json = $this->creditNoteBuilder->build($cn);

        try {
            $response = $this->send($json);

            $cn->sent_at = now();

            if (isset($response['errors'])) {
                $cn->sunat_status      = 'REJECTED';
                $cn->sunat_code        = $response['codigo'] ?? '400';
                $cn->sunat_description = $response['errors'];
            } elseif (isset($response['aceptada_por_sunat'])) {
                $cn->sunat_code        = $response['sunat_responsecode'] ?? null;
                $cn->sunat_description = $response['sunat_description'] ?? '';

                if ($response['aceptada_por_sunat'] === true) {
                    $cn->sunat_status = 'ACCEPTED';
                } else {
                    $cn->sunat_status = 'OBSERVED';
                    $cn->sunat_description = $response['sunat_description'] ?? 'Observada por SUNAT';
                }

                if (!empty($response['enlace_del_pdf'])) {
                    $cn->pdf_path = $response['enlace_del_pdf'];
                } elseif (!empty($response['enlace'])) {
                    $cn->pdf_path = rtrim($response['enlace'], '/') . '.pdf';
                }
                if (!empty($response['enlace_del_xml'])) {
                    $cn->xml_path = $response['enlace_del_xml'];
                } elseif (!empty($response['enlace'])) {
                    $cn->xml_path = rtrim($response['enlace'], '/') . '.xml';
                }
                if (!empty($response['enlace_del_cdr'])) {
                    $cn->cdr_path = $response['enlace_del_cdr'];
                } elseif (!empty($response['enlace'])) {
                    $cn->cdr_path = rtrim($response['enlace'], '/') . '.cdr';
                }

                $cn->hash = $response['codigo_hash'] ?? null;
            }
        } catch (Exception $e) {
            Log::error('NubeFact sendCreditNote error', [
                'cn_id' => $cn->id,
                'msg'   => $e->getMessage(),
            ]);
            $cn->sunat_status      = 'ERROR';
            $cn->sunat_code        = '500';
            $cn->sunat_description = $e->getMessage();
        }

        $cn->save();
        return $cn;
    }

    // ═══════════════════════════════════════════════════════════════
    //  ANULAR COMPROBANTE
    // ═══════════════════════════════════════════════════════════════

    /**
     * Anula un comprobante existente en NubeFact.
     */
    public function voidDocument(string $tipoComprobante, string $serie, int $numero, string $motivo): array
    {
        if (!$this->config->isNubefactConfigured()) {
            throw new \RuntimeException('NubeFact no está configurado.');
        }

        $json = [
            'operacion'            => 'generar_anulacion',
            'tipo_de_comprobante'  => (int) $tipoComprobante,
            'serie'                => $serie,
            'numero'               => $numero,
            'motivo'               => $motivo,
            'codigo_unico'         => '',
        ];

        return $this->send($json);
    }

    // ═══════════════════════════════════════════════════════════════
    //  CONSULTAR COMPROBANTE
    // ═══════════════════════════════════════════════════════════════

    /**
     * Consulta el estado de un comprobante en NubeFact.
     */
    public function queryDocument(string $tipoComprobante, string $serie, int $numero): array
    {
        if (!$this->config->isNubefactConfigured()) {
            throw new \RuntimeException('NubeFact no está configurado.');
        }

        $json = [
            'operacion'           => 'consultar_comprobante',
            'tipo_de_comprobante' => (int) $tipoComprobante,
            'serie'               => $serie,
            'numero'              => $numero,
        ];

        return $this->send($json);
    }

    // ═══════════════════════════════════════════════════════════════
    //  PAYLOAD QR (para representación impresa)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Construye el payload para el código QR SUNAT.
     * Formato: RUC|tipo|serie|numero|IGV|Total|Fecha|tipoDocCliente|nroDocCliente|hash
     */
    public function buildQrPayload(Order $order): string
    {
        return implode('|', [
            $this->config->ruc(),
            $order->document_type === 'Factura' ? '01' : '03',
            $order->serie,
            $order->correlativo,
            number_format((float) $order->igv, 2, '.', ''),
            number_format((float) $order->total, 2, '.', ''),
            now()->format('d-m-Y'),
            $this->guessClientDocType($order->client_document),
            $order->client_document ?: '-',
            $order->hash ?? '',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Envía el JSON a la API de NubeFact vía POST.
     *
     * @param  array  $json  Payload a enviar
     * @return array  Respuesta decodificada de NubeFact
     * @throws Exception si falla la conexión o el HTTP status no es 200
     */
    private function send(array $json): array
    {
        $ruta  = $this->config->nubefactRuta();
        $token = $this->config->nubefactToken();

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $ruta,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($json, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => [
                'Authorization: ' . $token,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Exception("Error de conexión con NubeFact: {$error}");
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            $msg = $data['errors'] ?? ("HTTP {$httpCode}: " . substr($response, 0, 500));
            throw new Exception("NubeFact respondió con error: {$msg}");
        }

        return $data ?? [];
    }

    private function guessClientDocType(?string $doc): string
    {
        $doc = trim((string) $doc);
        if (strlen($doc) === 8) return '1';
        if (strlen($doc) === 11) return '6';
        return '0';
    }
}
