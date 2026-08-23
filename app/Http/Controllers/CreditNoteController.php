<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Models\DocumentSeries;
use App\Models\Order;
use App\Services\Sunat\SunatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Gestión de Notas de Crédito Electrónicas (tipo 07 SUNAT).
 *
 * Una Nota de Crédito anula o ajusta un comprobante (boleta/factura) ya emitido.
 * Códigos de motivo (Catálogo 09 SUNAT):
 *   01 Anulación de la operación
 *   02 Anulación por error en el RUC
 *   03 Corrección por error en la descripción
 *   06 Devolución total
 *   07 Devolución por ítem
 *   13 Ajustes - montos y/o fechas de pago
 */
class CreditNoteController extends Controller
{
    public const REASON_CODES = [
        '01' => 'Anulación de la operación',
        '02' => 'Anulación por error en el RUC',
        '03' => 'Corrección por error en la descripción',
        '06' => 'Devolución total',
        '07' => 'Devolución por ítem',
        '13' => 'Ajustes - montos y/o fechas de pago',
    ];

    public function index(Request $request)
    {
        $query = CreditNote::with('order', 'user')->orderByDesc('created_at');

        if ($s = $request->input('status')) {
            $query->where('sunat_status', $s);
        }
        if ($q = $request->input('q')) {
            $query->where(function ($qq) use ($q) {
                $qq->where('serie', 'like', "%{$q}%")
                   ->orWhere('correlativo', 'like', "%{$q}%");
            });
        }

        $notes = $query->paginate(25)->withQueryString();
        return view('credit_notes.index', compact('notes'));
    }

    public function create(Order $order)
    {
        abort_unless(in_array($order->document_type, ['Boleta', 'Factura']), 404);

        if ($order->sunat_status !== 'ACCEPTED') {
            return redirect()->route('billing.show', $order)
                ->with('error', 'Solo se puede emitir nota de crédito sobre comprobantes ACEPTADOS por SUNAT.');
        }

        $order->load('details.product');
        return view('credit_notes.create', [
            'order'   => $order,
            'reasons' => self::REASON_CODES,
        ]);
    }

    public function store(Request $request, Order $order)
    {
        abort_unless(in_array($order->document_type, ['Boleta', 'Factura']), 404);

        $request->validate([
            'reason_code'        => 'required|string|in:01,02,03,06,07,13',
            'reason_description' => 'required|string|max:255',
        ]);

        $isFactura = $order->document_type === 'Factura';
        $seriesKey = $isFactura ? 'nota_credito_factura' : 'nota_credito_boleta';

        $cn = DB::transaction(function () use ($request, $order, $seriesKey) {
            $next = DocumentSeries::next($seriesKey);

            return CreditNote::create([
                'order_id'           => $order->id,
                'serie'              => $next['serie'],
                'correlativo'        => $next['correlativo'],
                'document_type'      => 'nota_credito',
                'reason_code'        => $request->input('reason_code'),
                'reason_description' => $request->input('reason_description'),
                'subtotal'           => $order->total_gravada,
                'igv'                => $order->igv,
                'total'              => $order->total,
                'sunat_status'       => 'PENDING',
                'user_id'            => Auth::id(),
            ]);
        });

        // Envío inmediato a SUNAT
        try {
            (new SunatService())->sendCreditNote($cn);
            $cn->refresh();
        } catch (\Throwable $e) {
            Log::error('CreditNote sendInvoice', ['cn_id' => $cn->id, 'msg' => $e->getMessage()]);
        }

        $msg = "Nota de Crédito {$cn->full_number}: {$cn->sunat_status}";
        return redirect()->route('credit_notes.show', $cn)
            ->with($cn->sunat_status === 'ACCEPTED' ? 'success' : 'error', $msg);
    }

    public function show(CreditNote $creditNote)
    {
        $creditNote->load('order.details.product');
        return view('credit_notes.show', ['cn' => $creditNote]);
    }

    public function retry(CreditNote $creditNote)
    {
        try {
            (new SunatService())->sendCreditNote($creditNote);
            $creditNote->refresh();
        } catch (\Throwable $e) {
            return back()->with('error', 'Excepción: ' . $e->getMessage());
        }

        $msg = "Nota de Crédito {$creditNote->full_number}: {$creditNote->sunat_status}";
        return back()->with($creditNote->sunat_status === 'ACCEPTED' ? 'success' : 'error', $msg);
    }

    public function downloadXml(CreditNote $creditNote)
    {
        return $this->stream($creditNote->xml_path, 'application/xml');
    }

    public function downloadCdr(CreditNote $creditNote)
    {
        return $this->stream($creditNote->cdr_path, 'application/zip');
    }

    private function stream(?string $relPath, string $mime)
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
