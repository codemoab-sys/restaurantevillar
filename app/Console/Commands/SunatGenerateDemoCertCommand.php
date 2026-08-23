<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * @deprecated Este comando era necesario para Greenter (SOAP/XML).
 * NubeFact NO requiere certificados digitales.
 */
class SunatGenerateDemoCertCommand extends Command
{
    protected $signature = 'sunat:cert:demo';

    protected $description = '[OBSOLETO] Certificados demo ya no son necesarios con NubeFact';

    public function handle(): int
    {
        $this->warn('Este comando es obsoleto.');
        $this->info('NubeFact NO requiere certificados digitales para enviar comprobantes.');
        $this->newLine();
        $this->info('Configura tu RUTA y TOKEN en: Configuración > Facturación Electrónica > NubeFact');

        return self::SUCCESS;
    }
}
