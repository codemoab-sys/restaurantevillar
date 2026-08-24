<?php

namespace App\Http\Controllers;

use App\Models\Setting;

class LandingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        $company = [
            'name'    => $settings['company_name'] ?? 'Mi Restaurante',
            'phone'   => $settings['company_phone'] ?? '',
            'address' => $settings['company_address'] ?? '',
            'logo'    => $this->imageUrl($settings['company_logo'] ?? null),
        ];

        $defaultSliders = $this->defaultSliders();

        $slides = [];
        for ($i = 1; $i <= 4; $i++) {
            $key = 'web_slider_' . $i;
            if (array_key_exists($key, $settings) && ($settings[$key] === '' || $settings[$key] === '0')) {
                continue;
            }
            if (!empty($settings[$key])) {
                $slides[] = $this->imageUrl($settings[$key], 'web/');
            } else {
                $slides[] = $defaultSliders[$i - 1];
            }
        }
        $slides = array_values(array_filter($slides));

        $web = [
            'hero_title'    => $settings['web_hero_title'] ?? 'Bienvenido a ' . $company['name'],
            'hero_subtitle' => $settings['web_hero_subtitle'] ?? 'Comida deliciosa preparada con ingredientes frescos y un servicio que nos hace únicos.',
            'hero_btn_text' => $settings['web_hero_btn_text'] ?? 'Ver Carta Digital',
            'slides'        => $slides,
            'show_hero'     => ($settings['web_show_hero'] ?? '1') === '1',

            'about_title'   => $settings['web_about_title'] ?? 'Nuestra historia',
            'about_text'    => $settings['web_about_text'] ?? 'Somos un restaurante comprometido con ofrecer platos de calidad, buen ambiente y una atención que se siente en cada visita.',
            'about_image'   => $this->imageUrl($settings['web_about_image'] ?? null, 'web/') ?? $this->defaultAboutImage(),
            'about_badge'   => $settings['web_about_badge'] ?? 'Nuestro compromiso',
            'show_about'    => ($settings['web_show_about'] ?? '1') === '1',

            'features'      => $this->decodeList($settings['web_features'] ?? null, $this->defaultFeatures()),
            'show_features' => ($settings['web_show_features'] ?? '1') === '1',

            'steps'         => $this->decodeList($settings['web_steps'] ?? null, $this->defaultSteps()),
            'show_steps'    => ($settings['web_show_steps'] ?? '1') === '1',

            'contact_title' => $settings['web_contact_title'] ?? 'Visítanos',
            'contact_phone' => $settings['web_contact_phone'] ?? ($settings['company_phone'] ?? ''),
            'contact_whatsapp' => $settings['web_contact_whatsapp'] ?? '',
            'contact_address'  => $settings['web_contact_address'] ?? ($settings['company_address'] ?? ''),
            'contact_hours'    => $settings['web_contact_hours'] ?? 'Lun – Dom: 12:00 pm – 10:00 pm',
            'contact_email'    => $settings['web_contact_email'] ?? '',
            'show_contact'     => ($settings['web_show_contact'] ?? '1') === '1',

            'footer_text'   => $settings['web_footer_text'] ?? ('© ' . date('Y') . ' ' . $company['name'] . ' — Todos los derechos reservados.'),
            'show_footer'   => ($settings['web_show_footer'] ?? '1') === '1',
        ];

        // ── Colores de marca (desde configuración) ──
        $brand = [
            'primary'       => $settings['color_primary']       ?? '#ff8c00',
            'primary_hover' => $settings['color_primary_hover'] ?? '#e07b00',
            'primary_soft'  => $settings['color_primary_soft']  ?? '#fff4e6',
            'text_color'    => $settings['brand_text_color']    ?? '#1e1e2d',
        ];

        $appearance = [
            'page_bg'   => $settings['web_page_bg'] ?? '#ffffff',
            'header_bg' => $settings['web_header_bg'] ?? '#ffffff',
        ];

        return view('landing.index', compact('company', 'web', 'brand', 'appearance'));
    }

    private function imageUrl(?string $path, string $prefix = ''): ?string
    {
        if (!$path) return null;
        return asset('storage/' . $prefix . $path);
    }

    private function defaultSliders(): array
    {
        return [
            'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=1920&q=80',
            'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1920&q=80',
            'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1920&q=80',
            'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=1920&q=80',
        ];
    }

    private function defaultAboutImage(): string
    {
        return 'https://images.unsplash.com/photo-1552566626-52f8b828add9?auto=format&fit=crop&w=1200&q=80';
    }

    private function decodeList(?string $json, array $default): array
    {
        $decoded = $json ? json_decode($json, true) : null;
        return is_array($decoded) && count($decoded) > 0 ? $decoded : $default;
    }

    private function defaultFeatures(): array
    {
        return [
            ['icon' => 'bi-bicycle',          'title' => 'Delivery a Domicilio', 'text' => 'Recibe tus platos favoritos en la comodidad de tu hogar.'],
            ['icon' => 'bi-calendar-check-fill', 'title' => 'Reserva de Mesas',    'text' => 'Reserva con anticipación y evita largas esperas.'],
            ['icon' => 'bi-people-fill',      'title' => 'Eventos Especiales',    'text' => 'Celebramos cumpleaños, reuniones y fechas importantes.'],
            ['icon' => 'bi-qr-code-scan',     'title' => 'Carta Digital',         'text' => 'Escanea el código QR y explora la carta desde tu celular.'],
        ];
    }

    private function defaultSteps(): array
    {
        return [
            ['icon' => 'bi-1-circle-fill', 'title' => 'Elige tus platos', 'text' => 'Explora nuestra carta digital y elige lo que más te guste.'],
            ['icon' => 'bi-upc-scan',      'title' => 'Escanea el QR',    'text' => 'Usa la cámara de tu celular para abrir el menú en segundos.'],
            ['icon' => 'bi-wallet2',       'title' => 'Paga al recibir',  'text' => 'Efectivo, tarjeta o billeteras digitales. Tú eliges cómo pagar.'],
            ['icon' => 'bi-heart-fill',    'title' => 'Disfruta',         'text' => 'Déjanos sorprenderte con el mejor sabor de la zona.'],
        ];
    }
}
