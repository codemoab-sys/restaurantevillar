<?php

namespace App\Services\Sunat;

class NumeroLetras
{
    private static $unidades = [
        '', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO',
        'SEIS', 'SIETE', 'OCHO', 'NUEVE', 'DIEZ',
        'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE',
        'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE', 'VEINTE',
    ];

    private static $decenas = [
        '', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA',
        'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA', 'CIEN',
    ];

    private static $centenas = [
        '', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS',
        'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS',
    ];

    private static $escala = ['', 'MIL', 'MILLON', 'MIL MILLONES'];

    public static function convert(float $numero): string
    {
        $numero = round($numero, 2);
        $partes = explode('.', number_format($numero, 2, '.', ''));
        $enteros = (int) $partes[0];
        $decimales = (int) $partes[1];

        if ($enteros === 0 && $decimales === 0) {
            return 'CERO';
        }

        $resultado = '';
        if ($enteros > 0) {
            $resultado = self::convertirEnteros($enteros);
        }

        if ($decimales > 0) {
            $resultado .= ' CON ' . str_pad($decimales, 2, '0', STR_PAD_LEFT) . '/100';
        }

        return trim($resultado);
    }

    private static function convertirEnteros(int $numero): string
    {
        if ($numero === 100) {
            return 'CIEN';
        }

        $bloques = [];
        while ($numero > 0) {
            $bloques[] = $numero % 1000;
            $numero = intdiv($numero, 1000);
        }

        $partes = [];
        foreach ($bloques as $i => $bloque) {
            if ($bloque === 0) {
                continue;
            }
            $texto = self::convertirBloque($bloque);
            if ($i > 0 && $bloque === 1 && $i === 2) {
                $texto = 'MILLÓN';
            } elseif ($i > 1 && $bloque === 1) {
                $texto = 'MILLÓN';
            }
            if ($i > 0) {
                $texto .= ' ' . self::$escala[$i];
            }
            $partes[] = $texto;
        }

        return implode(' ', array_reverse($partes));
    }

    private static function convertirBloque(int $numero): string
    {
        $centena = intdiv($numero, 100);
        $resto = $numero % 100;
        $partes = [];

        if ($centena > 0) {
            $partes[] = self::$centenas[$centena];
        }

        if ($resto > 0) {
            if ($resto < 21) {
                $partes[] = self::$unidades[$resto];
            } else {
                $decena = intdiv($resto, 10);
                $unidad = $resto % 10;
                $partes[] = self::$decenas[$decena];
                if ($unidad > 0) {
                    $partes[count($partes) - 1] .= ' Y ' . self::$unidades[$unidad];
                }
            }
        }

        return implode(' ', $partes);
    }
}
