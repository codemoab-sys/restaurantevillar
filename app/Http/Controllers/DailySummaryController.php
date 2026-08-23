<?php

namespace App\Http\Controllers;

use App\Models\DailySummary;
use App\Services\Sunat\SunatConfig;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Resumen Diario de Boletas (RC) - SUNAT.
 *
 * NOTA: NubeFact NO soporta Resumen Diario vía API REST.
 * Las boletas se envían individualmente al momento de la venta.
 * Este módulo se mantiene para consulta histórica de resumenes
 * generados con el sistema anterior (Greenter).
 */
class DailySummaryController extends Controller
{
    public function index()
    {
        $summaries = DailySummary::with('user')
            ->orderByDesc('reference_date')
            ->orderByDesc('correlativo')
            ->paginate(25);

        return view('daily_summaries.index', compact('summaries'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'reference_date' => 'required|date|before_or_equal:today',
        ]);

        return back()->with('error',
            'NubeFact no soporta Resumen Diario vía API REST. ' .
            'Las boletas se envían individualmente al momento de la venta.'
        );
    }

    public function check(DailySummary $summary)
    {
        return back()->with('error',
            'La consulta de tickets de Resumen Diario no está disponible con NubeFact.'
        );
    }

    public function downloadXml(DailySummary $summary)
    {
        return $this->stream($summary->xml_path, 'application/xml');
    }

    public function downloadCdr(DailySummary $summary)
    {
        return $this->stream($summary->cdr_path, 'application/zip');
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
