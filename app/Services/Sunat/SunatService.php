<?php

namespace App\Services\Sunat;

use App\Models\CreditNote;
use App\Models\DailySummary;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * Fachada de compatibilidad para el envío de comprobantes a SUNAT.
 *
 * Delega toda la lógica a NubeFactService (API REST JSON).
 * Mantiene la misma interfaz pública que usan los controllers.
 */
class SunatService
{
    private NubeFactService $nubefact;

    public function __construct(?SunatConfig $config = null)
    {
        $this->nubefact = new NubeFactService($config);
    }

    /**
     * Envía una Factura o Boleta a SUNAT vía NubeFact.
     */
    public function sendInvoice(Order $order): Order
    {
        return $this->nubefact->sendInvoice($order);
    }

    /**
     * Envía una Nota de Crédito a SUNAT vía NubeFact.
     */
    public function sendCreditNote(CreditNote $cn): CreditNote
    {
        return $this->nubefact->sendCreditNote($cn);
    }

    /**
     * Anula un comprobante en NubeFact.
     *
     * @param  string  $tipoComprobante  1=Factura, 2=Boleta, 3=NC, 4=ND
     * @param  string  $serie
     * @param  int     $numero
     * @param  string  $motivo
     * @return array   Respuesta de NubeFact
     */
    public function voidDocument(string $tipoComprobante, string $serie, int $numero, string $motivo): array
    {
        return $this->nubefact->voidDocument($tipoComprobante, $serie, $numero, $motivo);
    }

    /**
     * Consulta el estado de un comprobante en NubeFact.
     */
    public function queryDocument(string $tipoComprobante, string $serie, int $numero): array
    {
        return $this->nubefact->queryDocument($tipoComprobante, $serie, $numero);
    }

    /**
     * Construye el payload para el código QR SUNAT.
     */
    public function buildQrPayload(Order $order): string
    {
        return $this->nubefact->buildQrPayload($order);
    }

    // ═══════════════════════════════════════════════════════════════
    //  MÉTODOS DEPRECADOS (mantenidos por compatibilidad)
    // ═══════════════════════════════════════════════════════════════

    /**
     * @deprecated Usar voidDocument() en su lugar.
     */
    public function sendSummary(DailySummary $summaryModel, $greenterSummary = null): DailySummary
    {
        Log::warning('SunatService::sendSummary() está obsoleto. Use NubeFact directamente.');

        $summaryModel->sunat_status      = 'ERROR';
        $summaryModel->sunat_code        = '501';
        $summaryModel->sunat_description = 'Resumen Diario no soportado vía NubeFact. Use notas de crédito.';
        $summaryModel->save();

        return $summaryModel;
    }

    /**
     * @deprecated Ya no aplica para NubeFact.
     */
    public function checkSummaryTicket(DailySummary $summary): DailySummary
    {
        Log::warning('SunatService::checkSummaryTicket() es obsoleto para NubeFact.');
        return $summary;
    }
}
