<?php

namespace App\Services\Sunat;

use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\Order;

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

    public function sendInvoice(Order $order): Order
    {
        return $this->nubefact->sendInvoice($order);
    }

    public function sendCreditNote(CreditNote $cn): CreditNote
    {
        return $this->nubefact->sendCreditNote($cn);
    }

    public function sendDebitNote(DebitNote $dn): DebitNote
    {
        return $this->nubefact->sendDebitNote($dn);
    }

    public function voidDocument(string $tipoComprobante, string $serie, int $numero, string $motivo): array
    {
        return $this->nubefact->voidDocument($tipoComprobante, $serie, $numero, $motivo);
    }

    public function queryDocument(string $tipoComprobante, string $serie, int $numero): array
    {
        return $this->nubefact->queryDocument($tipoComprobante, $serie, $numero);
    }

    public function buildQrPayload(Order $order): string
    {
        return $this->nubefact->buildQrPayload($order);
    }
}
