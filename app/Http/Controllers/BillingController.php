<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Models\DailySummary;
use App\Models\Order;
use App\Services\Sunat\SunatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Módulo de Comprobantes Electrónicos (SUNAT - Perú).
 *
 * Listado, detalle, reintento de envío, descarga de XML/CDR/PDF
 * tanto para boletas/facturas como para notas de crédito.
 */
class BillingController extends Controller
{
    /**
     * Listado con filtros (estado, tipo, fecha, búsqueda libre).
     */
    public function index(Request $request)
    {
        $query = Order::with('user')
            ->whereIn('document_type', ['Boleta', 'Factura'])
            ->whereNotNull('serie')
            ->whereNotNull('correlativo')
            ->orderByDesc('created_at');

        if ($s = $request->input('status')) {
            $query->where('sunat_status', $s);
        }
        if ($t = $request->input('type')) {
            $query->where('document_type', $t);
        }
        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($q = $request->input('q')) {
            $query->where(function ($qq) use ($q) {
                $qq->where('serie', 'like', "%{$q}%")
                   ->orWhere('correlativo', 'like', "%{$q}%")
                   ->orWhere('client_name', 'like', "%{$q}%")
                   ->orWhere('client_document', 'like', "%{$q}%");
            });
        }

        $orders = $query->paginate(25)->withQueryString();

        // Totales rápidos por estado (sin filtros)
        $stats = Order::whereIn('document_type', ['Boleta', 'Factura'])
            ->selectRaw('sunat_status, COUNT(*) as total')
            ->groupBy('sunat_status')
            ->pluck('total', 'sunat_status')
            ->toArray();

        return view('billing.index', compact('orders', 'stats'));
    }

    /**
     * Detalle de un comprobante.
     */
    public function show(Order $order)
    {
        abort_unless(in_array($order->document_type, ['Boleta', 'Factura']), 404);
        $order->load('details.product', 'creditNotes');
        return view('billing.show', compact('order'));
    }

    /**
     * Reenvía un comprobante a SUNAT.
     */
    public function retry(Order $order)
    {
        abort_unless(in_array($order->document_type, ['Boleta', 'Factura']), 404);

        if (!$order->serie || !$order->correlativo) {
            return back()->with('error', 'La orden no tiene serie/correlativo asignados.');
        }

        try {
            (new SunatService())->sendInvoice($order->fresh('details.product'));
            $order->refresh();
        } catch (\Throwable $e) {
            Log::error('Reintento SUNAT falló', ['order_id' => $order->id, 'msg' => $e->getMessage()]);
            return back()->with('error', 'Excepción: ' . $e->getMessage());
        }

        $msg = "Comprobante {$order->full_number}: {$order->sunat_status}";
        if ($order->sunat_description) {
            $msg .= " - {$order->sunat_description}";
        }

        return back()->with($order->sunat_status === 'ACCEPTED' ? 'success' : 'error', $msg);
    }

    /**
     * Descarga el XML firmado.
     */
    public function downloadXml(Order $order)
    {
        return $this->streamSunatFile($order->xml_path, 'application/xml');
    }

    /**
     * Descarga el CDR (zip).
     */
    public function downloadCdr(Order $order)
    {
        return $this->streamSunatFile($order->cdr_path, 'application/zip');
    }

    /**
     * Streaming seguro de archivos dentro de storage/app/sunat/.
     */
    private function streamSunatFile(?string $relPath, string $mime): Response
    {
        if (!$relPath || !Storage::disk('local')->exists($relPath)) {
            abort(404, 'Archivo no encontrado.');
        }

        return response()->stream(function () use ($relPath) {
            echo Storage::disk('local')->get($relPath);
        }, 200, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'attachment; filename="' . basename($relPath) . '"',
        ]);
    }
}
