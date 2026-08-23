<?php

namespace Database\Seeders;

use App\Models\DocumentSeries;
use Illuminate\Database\Seeder;

class DocumentSeriesSeeder extends Seeder
{
    public function run(): void
    {
        // IMPORTANTE: Las series DEBEN coincidir con las configuradas en NubeFact.
        // Ejemplo: Si en NubeFact tu serie de factura es "FPP1", aquí debe ser "FPP1".
        $series = [
            ['document_code' => '01', 'document_type' => 'factura',              'serie' => 'FPP1'],
            ['document_code' => '03', 'document_type' => 'boleta',               'serie' => 'BPP1'],
            ['document_code' => '07', 'document_type' => 'nota_credito_factura', 'serie' => 'FPP1'],
            ['document_code' => '07', 'document_type' => 'nota_credito_boleta',  'serie' => 'BPP1'],
        ];

        foreach ($series as $s) {
            DocumentSeries::updateOrCreate(
                ['document_type' => $s['document_type']],
                array_merge($s, ['last_number' => 0, 'is_active' => true])
            );
        }
    }
}
