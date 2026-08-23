<?php

namespace App\Http\Controllers;

use App\Models\DocumentSeries;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        $colorDefaults = [
            'color_primary'        => '#ff8c00',
            'color_primary_hover'  => '#e07b00',
            'color_primary_soft'   => '#fff4e6',
            'color_sidebar_bg'     => '#2d1b5e',
            'color_sidebar_active' => '#ff8c00',
        ];

        foreach ($colorDefaults as $key => $value) {
            if (!isset($settings[$key])) {
                $settings[$key] = $value;
            }
        }

        $timezones = [
            'America/Lima' => '(UTC-05:00) Lima, Bogotá, Quito',
            'America/Caracas' => '(UTC-04:00) Caracas',
            'America/La_Paz' => '(UTC-04:00) La Paz',
            'America/Santiago' => '(UTC-03:00) Santiago',
            'America/Argentina/Buenos_Aires' => '(UTC-03:00) Buenos Aires',
            'America/Montevideo' => '(UTC-03:00) Montevideo',
            'America/Mexico_City' => '(UTC-06:00) Ciudad de México',
            'America/Tijuana' => '(UTC-08:00) Tijuana',
            'America/New_York' => '(UTC-05:00) Hora del Este (EE.UU.)',
            'Europe/Madrid' => '(UTC+01:00) Madrid',
            'UTC' => '(UTC+00:00) Tiempo Universal Coordinado'
        ];

        return view('settings.index', compact('settings', 'timezones'));
    }

    public function update(Request $request)
    {
        $except = [
            '_token', 'company_logo',
            'serie_factura', 'serie_boleta', 'serie_nc_factura', 'serie_nc_boleta',
            'correlativo_factura', 'correlativo_boleta', 'correlativo_nc_factura', 'correlativo_nc_boleta',
        ];
        $data = $request->except($except);

        foreach ($data as $key => $value) {
            if (is_null($value)) continue;
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        if ($request->hasFile('company_logo')) {
            $request->validate(['company_logo' => 'image|max:2048']);
            $oldLogo = Setting::where('key', 'company_logo')->value('value');
            if ($oldLogo) {
                $this->deleteLogoFiles($oldLogo);
            }
            $path = $request->file('company_logo')->store('settings', 'public');
            $this->mirrorLogoToPublic($path);
            Setting::updateOrCreate(['key' => 'company_logo'], ['value' => $path]);
        }

        $this->saveSeries('factura', $request->input('serie_factura', 'FPP1'), (int) $request->input('correlativo_factura', 1));
        $this->saveSeries('boleta', $request->input('serie_boleta', 'BPP1'), (int) $request->input('correlativo_boleta', 1));
        $this->saveSeries('nota_credito_factura', $request->input('serie_nc_factura', 'FPP1'), (int) $request->input('correlativo_nc_factura', 1));
        $this->saveSeries('nota_credito_boleta', $request->input('serie_nc_boleta', 'BPP1'), (int) $request->input('correlativo_nc_boleta', 1));

        return redirect()->back()->with('success', 'Configuración actualizada correctamente.');
    }

    /**
     * Guarda serie y correlativo.
     * Si el usuario pide inicio en 5, last_number = 4, así el próximo será 5.
     */
    private function saveSeries(string $documentType, string $serie, int $startNumber): void
    {
        $code = match ($documentType) {
            'factura' => '01',
            'boleta' => '03',
            default => '07',
        };

        DocumentSeries::updateOrCreate(
            ['document_type' => $documentType],
            [
                'document_code' => $code,
                'serie' => strtoupper(trim($serie)),
                'last_number' => max(0, $startNumber - 1),
                'is_active' => true,
            ]
        );
    }

    private function deleteLogoFiles(?string $path): void
    {
        if (!$path) return;
        $files = [
            storage_path('app/public/' . $path),
            public_path('storage/' . $path),
        ];
        foreach ($files as $file) {
            if (is_file($file)) @unlink($file);
        }
    }

    private function mirrorLogoToPublic(string $path): void
    {
        $source = storage_path('app/public/' . $path);
        $target = public_path('storage/' . $path);
        if (!is_file($source)) return;
        $dir = dirname($target);
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        if (!file_exists($target)) @copy($source, $target);
    }
}
