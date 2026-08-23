<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Sunat\SunatService;
use Illuminate\Console\Command;

/**
 * Reintenta envíos a SUNAT de órdenes que quedaron en estado no exitoso.
 *
 * Uso:
 *   php artisan sunat:retry                       # Reintenta todos los PENDING/ERROR
 *   php artisan sunat:retry --status=ERROR        # Solo ERROR
 *   php artisan sunat:retry --order=15            # Una orden específica
 *   php artisan sunat:retry --limit=5             # Como máximo 5 por ejecución
 */
class SunatRetryCommand extends Command
{
    protected $signature = 'sunat:retry
                            {--order= : Reintenta solo la orden con este ID}
                            {--status=* : Estados a reintentar (default: PENDING, ERROR, REJECTED)}
                            {--limit=20 : Máximo de órdenes por ejecución}';

    protected $description = 'Reintenta envíos a SUNAT de comprobantes pendientes o con error';

    public function handle(): int
    {
        $service = new SunatService();

        // Estados por defecto a reintentar
        $statuses = $this->option('status') ?: ['PENDING', 'ERROR', 'REJECTED'];

        $query = Order::query()
            ->whereIn('document_type', ['Boleta', 'Factura'])
            ->whereNotNull('serie')
            ->whereNotNull('correlativo');

        if ($this->option('order')) {
            $query->where('id', (int) $this->option('order'));
        } else {
            $query->whereIn('sunat_status', $statuses)
                  ->limit((int) $this->option('limit'));
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {
            $this->info('No hay órdenes para reintentar.');
            return self::SUCCESS;
        }

        $this->info("Reintentando {$orders->count()} orden(es)...");
        $this->newLine();

        $ok      = 0;
        $failed  = 0;

        foreach ($orders as $order) {
            $this->line("→ Orden #{$order->id} {$order->full_number} ({$order->sunat_status})");

            try {
                $service->sendInvoice($order->fresh('details.product'));
                $order->refresh();

                $color = match ($order->sunat_status) {
                    'ACCEPTED' => 'info',
                    'OBSERVED' => 'comment',
                    default    => 'error',
                };
                $this->{$color}("   ↳ {$order->sunat_status}  [{$order->sunat_code}] {$order->sunat_description}");

                if ($order->sunat_status === 'ACCEPTED') {
                    $ok++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $this->error('   ↳ EXCEPCIÓN: ' . $e->getMessage());
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Resumen:  ✓ {$ok} aceptadas   ✗ {$failed} con problema");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
