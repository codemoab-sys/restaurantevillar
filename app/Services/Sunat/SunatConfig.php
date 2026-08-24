<?php

namespace App\Services\Sunat;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

/**
 * Lee y centraliza la configuración SUNAT / NubeFact almacenada en la tabla settings.
 */
class SunatConfig
{
    private array $settings;

    public function __construct(?array $settings = null)
    {
        $this->settings = $settings ?? Setting::pluck('value', 'key')->toArray();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    // ═══════════════════════════════════════════════════════════════
    //  DATOS DEL EMISOR (para el JSON de NubeFact)
    // ═══════════════════════════════════════════════════════════════

    public function ruc(): string
    {
        return trim((string) $this->get('sunat_ruc', ''));
    }

    public function razonSocial(): string
    {
        return trim((string) $this->get('sunat_razon_social', ''));
    }

    public function nombreComercial(): string
    {
        return (string) $this->get('sunat_nombre_comercial', $this->razonSocial());
    }

    public function direccion(): string
    {
        return (string) $this->get('sunat_direccion_fiscal', '-');
    }

    public function ubigeo(): string
    {
        return trim((string) $this->get('sunat_ubigeo', ''));
    }

    public function departamento(): string
    {
        return (string) $this->get('sunat_departamento', 'LIMA');
    }

    public function provincia(): string
    {
        return (string) $this->get('sunat_provincia', 'LIMA');
    }

    public function distrito(): string
    {
        return (string) $this->get('sunat_distrito', 'LIMA');
    }

    public function urbanizacion(): string
    {
        return (string) $this->get('sunat_urbanizacion', '-');
    }

    public function codigoPais(): string
    {
        return (string) $this->get('sunat_codigo_pais', 'PE');
    }

    public function igvRate(): float
    {
        return (float) $this->get('sunat_igv_rate', 18);
    }

    public function igvFactor(): float
    {
        return $this->igvRate() / 100;
    }

    public function isProduction(): bool
    {
        return $this->get('sunat_environment', 'beta') === 'produccion';
    }

    // ═══════════════════════════════════════════════════════════════
    //  CREDENCIALES NUBEFACT
    // ═══════════════════════════════════════════════════════════════

    /**
     * RUTA de la API NubeFact.
     * Ejemplo ONLINE: https://api.nubefact.com/api/v1/48239908-7ae7-4353-824d-071765d4
     * Ejemplo OFFLINE: http://localhost:8000/api/v1/48239908-7ae7-4353-824d-071765d4
     */
    public function nubefactRuta(): string
    {
        return (string) $this->get('nubefact_ruta', '');
    }

    /**
     * TOKEN de autenticación de NubeFact.
     */
    public function nubefactToken(): string
    {
        $token = (string) $this->get('nubefact_token', '');

        if (!str_starts_with($token, 'enc:')) {
            return $token;
        }

        try {
            return Crypt::decryptString(substr($token, 4));
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Verifica que NubeFact esté configurado (RUTA + TOKEN).
     */
    public function isNubefactConfigured(): bool
    {
        return filter_var($this->nubefactRuta(), FILTER_VALIDATE_URL) !== false
            && !empty($this->nubefactToken())
            && preg_match('/^\d{11}$/', $this->ruc()) === 1
            && $this->razonSocial() !== ''
            && preg_match('/^\d{6}$/', $this->ubigeo()) === 1;
    }
}
