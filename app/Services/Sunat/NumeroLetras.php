<?php

namespace App\Services\Sunat;

/**
 * Conversor de número a letras (formato SUNAT).
 * Compartido entre los builders y las vistas PDF.
 */
class NumeroLetras
{
    public static function convert(float $amount): string
    {
        $entero  = (int) floor($amount);
        $decimal = (int) round(($amount - $entero) * 100);

        return strtoupper(self::numeroALetras($entero)) . sprintf(' CON %02d/100', $decimal);
    }

    private static function numeroALetras(int $n): string
    {
        if ($n === 0) return 'CERO';
        if ($n < 0)  return 'MENOS ' . self::numeroALetras(-$n);

        $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
                     'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS',
                     'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE', 'VEINTE'];
        $decenas  = ['', '', 'VEINTI', 'TREINTA', 'CUARENTA', 'CINCUENTA',
                     'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS',
                     'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        if ($n <= 20) return $unidades[$n];

        if ($n < 100) {
            $d = intdiv($n, 10);
            $u = $n % 10;
            if ($d === 2) {
                return $u === 0 ? 'VEINTE' : 'VEINTI' . strtolower($unidades[$u]);
            }
            return $decenas[$d] . ($u ? ' Y ' . $unidades[$u] : '');
        }

        if ($n < 1000) {
            if ($n === 100) return 'CIEN';
            $c = intdiv($n, 100);
            $r = $n % 100;
            return $centenas[$c] . ($r ? ' ' . self::numeroALetras($r) : '');
        }

        if ($n < 1_000_000) {
            $miles  = intdiv($n, 1000);
            $resto  = $n % 1000;
            $prefijo = $miles === 1 ? 'MIL' : self::numeroALetras($miles) . ' MIL';
            return $prefijo . ($resto ? ' ' . self::numeroALetras($resto) : '');
        }

        $millones = intdiv($n, 1_000_000);
        $resto    = $n % 1_000_000;
        $prefijo  = $millones === 1 ? 'UN MILLON' : self::numeroALetras($millones) . ' MILLONES';
        return $prefijo . ($resto ? ' ' . self::numeroALetras($resto) : '');
    }
}
