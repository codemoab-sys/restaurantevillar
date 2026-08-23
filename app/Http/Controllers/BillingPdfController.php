<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Sunat\SunatConfig;
use Illuminate\Http\RedirectResponse;

/**
 * Genera la representación gráfica imprimible del comprobante.
 *
 * - Facturas: redirige al PDF de NubeFact (enlace_del_pdf).
 * - Boletas: genera ticket 80mm personalizado SIN QR (NubeFact maneja el QR).
 */
class BillingPdfController extends Controller
{
    /**
     * Para Facturas y Boletas redirige al PDF de NubeFact.
     * Para Nota Venta (ticket) muestra el diseño personalizado.
     */
    public function show(Order $order)
    {
        abort_unless(in_array($order->document_type, ['Boleta', 'Factura']), 404);

        // Factura o Boleta → redirigir al PDF de NubeFact
        if (!empty($order->pdf_path)) {
            return redirect($order->pdf_path);
        }

        // Fallback si no hay enlace de NubeFact
        $order->load('details.product');
        return view('billing.pdf.a4', [
            'order'   => $order,
            'config'  => new SunatConfig(),
            'company' => $this->companyData(),
        ]);
    }

    /**
     * Ticket 80mm - Nota de venta (diseño personalizado, NubeFact no lo genera).
     */
    public function ticket(Order $order)
    {
        abort_unless(in_array($order->document_type, ['Boleta', 'Factura']), 404);
        $order->load('details.product');

        return view('billing.pdf.ticket', [
            'order'   => $order,
            'config'  => new SunatConfig(),
            'company' => $this->companyData(),
        ]);
    }

    private function companyData(): array
    {
        $config = new SunatConfig();
        return [
            'ruc'              => $config->ruc(),
            'razon_social'     => $config->razonSocial(),
            'nombre_comercial' => $config->nombreComercial(),
            'direccion'        => $config->direccion(),
        ];
    }
}
