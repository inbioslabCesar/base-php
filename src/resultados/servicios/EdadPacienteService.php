<?php

class EdadPacienteService
{
    private const TZ = 'America/Lima';

    public static function resolverEdadParaEvento($fechaNacimientoRaw, $fechaReferenciaRaw, $edadFallbackRaw = null, $edadFallbackReferenceRaw = null)
    {
        $fechaNacimiento = self::parseDate($fechaNacimientoRaw, true);
        $fechaReferencia = self::parseDate($fechaReferenciaRaw, false);

        if ($fechaNacimiento instanceof DateTimeImmutable && $fechaReferencia instanceof DateTimeImmutable) {
            if ($fechaReferencia < $fechaNacimiento) {
                $fechaReferencia = $fechaNacimiento;
            }

            $diff = $fechaNacimiento->diff($fechaReferencia);
            $years = (int)$diff->y;
            $months = (int)$diff->m;
            $days = (int)$diff->d;
            $totalDays = (int)$diff->format('%a');
            $edadValor = self::yearsDecimal($years, $months, $days);

            return [
                'edad_valor' => self::formatDecimal($edadValor),
                'edad_texto' => self::formatearEdadDetallada($years, $months, $days),
                'edad_anios' => $years,
                'edad_meses' => $months,
                'edad_dias' => $days,
                'edad_dias_total' => $totalDays,
                'fuente' => 'fecha',
            ];
        }

        $fallback = self::parseLegacyEdad($edadFallbackRaw);
        if ($fallback !== null) {
            $edadValorFallback = (float)$fallback['edad_valor'];
            $fuenteFallback = 'fallback';

            $fechaBaseFallback = self::parseDate($edadFallbackReferenceRaw, false);
            if ($fechaBaseFallback instanceof DateTimeImmutable && $fechaReferencia instanceof DateTimeImmutable) {
                $segundos = $fechaReferencia->getTimestamp() - $fechaBaseFallback->getTimestamp();
                if ($segundos > 0) {
                    $edadValorFallback += ($segundos / 86400.0) / 365.2425;
                    $fuenteFallback = 'fallback-proyectada';
                }
            }

            $edadTextoFallback = self::formatearEdadDetalladaDesdeValor($edadValorFallback, self::formatearEdadDetallada((int)$fallback['years'], (int)$fallback['months'], (int)$fallback['days']));
            $desgloseFallback = self::desglosarAniosDecimales($edadValorFallback);

            return [
                'edad_valor' => self::formatDecimal($edadValorFallback),
                'edad_texto' => $edadTextoFallback,
                'edad_anios' => $desgloseFallback['years'],
                'edad_meses' => $desgloseFallback['months'],
                'edad_dias' => $desgloseFallback['days'],
                'edad_dias_total' => null,
                'fuente' => $fuenteFallback,
            ];
        }

        return [
            'edad_valor' => null,
            'edad_texto' => '',
            'edad_anios' => null,
            'edad_meses' => null,
            'edad_dias' => null,
            'edad_dias_total' => null,
            'fuente' => 'none',
        ];
    }

    public static function convertirEdadTextoADecimal($rawEdad): ?float
    {
        $fallback = self::parseLegacyEdad($rawEdad);
        if ($fallback === null) {
            return null;
        }
        return (float)$fallback['edad_valor'];
    }

    public static function formatearEdadDetallada(int $years, int $months, int $days): string
    {
        return $years . ' ' . ($years === 1 ? 'año' : 'años')
            . ' ' . $months . ' ' . ($months === 1 ? 'mes' : 'meses')
            . ' ' . $days . ' ' . ($days === 1 ? 'día' : 'días');
    }

    public static function formatearEdadDetalladaDesdeValor($rawEdadValor, $rawEdadTextoFallback = ''): string
    {
        $value = null;
        if ($rawEdadValor !== null && $rawEdadValor !== '') {
            $tmp = str_replace(',', '.', trim((string)$rawEdadValor));
            if (is_numeric($tmp)) {
                $value = max(0.0, (float)$tmp);
            }
        }

        if ($value !== null) {
            $years = (int)floor($value);
            $monthsFloat = ($value - $years) * 12;
            $months = (int)floor($monthsFloat);
            $days = (int)round(($monthsFloat - $months) * 30.4375);

            if ($days >= 30) {
                $days -= 30;
                $months++;
            }
            if ($months >= 12) {
                $months -= 12;
                $years++;
            }

            return self::formatearEdadDetallada($years, $months, $days);
        }

        $fallback = self::parseLegacyEdad($rawEdadTextoFallback);
        if ($fallback !== null) {
            return self::formatearEdadDetallada((int)$fallback['years'], (int)$fallback['months'], (int)$fallback['days']);
        }

        return self::formatearEdadDetallada(0, 0, 0);
    }

    private static function parseDate($raw, bool $asStartOfDay): ?DateTimeImmutable
    {
        $text = trim((string)($raw ?? ''));
        if ($text === '') {
            return null;
        }

        $tz = new DateTimeZone(self::TZ);
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y',
            DateTimeInterface::ATOM,
        ];

        foreach ($formats as $fmt) {
            $dt = DateTimeImmutable::createFromFormat($fmt, $text, $tz);
            if ($dt instanceof DateTimeImmutable) {
                return $asStartOfDay ? $dt->setTime(0, 0, 0) : $dt->setTime((int)$dt->format('H'), (int)$dt->format('i'), (int)$dt->format('s'));
            }
        }

        try {
            $dt = new DateTimeImmutable($text, $tz);
            return $asStartOfDay ? $dt->setTime(0, 0, 0) : $dt;
        } catch (Throwable $e) {
            return null;
        }
    }

    private static function parseLegacyEdad($raw): ?array
    {
        $text = trim((string)($raw ?? ''));
        if ($text === '') {
            return null;
        }

        $normalized = mb_strtolower($text, 'UTF-8');
        $normalized = str_replace(['anios', 'año', 'años'], 'years', $normalized);
        $normalized = str_replace(['mes', 'meses'], 'months', $normalized);
        $normalized = str_replace(['dia', 'dias', 'día', 'días'], 'days', $normalized);

        $years = 0;
        $months = 0;
        $days = 0;

        if (preg_match('/(\d+)\s*years/u', $normalized, $mY)) {
            $years = (int)$mY[1];
        }
        if (preg_match('/(\d+)\s*months/u', $normalized, $mM)) {
            $months = (int)$mM[1];
        }
        if (preg_match('/(\d+)\s*days/u', $normalized, $mD)) {
            $days = (int)$mD[1];
        }

        if ($years === 0 && $months === 0 && $days === 0) {
            if (preg_match('/-?\d+(?:[\.,]\d+)?/u', $normalized, $m)) {
                $num = (float)str_replace(',', '.', $m[0]);
                if (!is_finite($num) || $num < 0) {
                    return null;
                }
                $years = (int)floor($num);
                $monthsFloat = ($num - $years) * 12;
                $months = (int)floor($monthsFloat);
                $days = (int)round(($monthsFloat - $months) * 30.4375);
            } else {
                return null;
            }
        }

        while ($days >= 30) {
            $days -= 30;
            $months++;
        }
        while ($months >= 12) {
            $months -= 12;
            $years++;
        }

        return [
            'edad_valor' => self::yearsDecimal($years, $months, $days),
            'years' => $years,
            'months' => $months,
            'days' => $days,
        ];
    }

    private static function yearsDecimal(int $years, int $months, int $days): float
    {
        $value = $years + ($months / 12.0) + ($days / 365.2425);
        if (!is_finite($value) || $value < 0) {
            return 0.0;
        }
        return $value;
    }

    private static function desglosarAniosDecimales(float $value): array
    {
        $value = max(0.0, $value);
        $years = (int)floor($value);
        $monthsFloat = ($value - $years) * 12;
        $months = (int)floor($monthsFloat);
        $days = (int)round(($monthsFloat - $months) * 30.4375);

        if ($days >= 30) {
            $days -= 30;
            $months++;
        }
        if ($months >= 12) {
            $months -= 12;
            $years++;
        }

        return [
            'years' => $years,
            'months' => $months,
            'days' => $days,
        ];
    }

    private static function formatDecimal(float $value): string
    {
        $out = number_format($value, 6, '.', '');
        $out = rtrim(rtrim($out, '0'), '.');
        return $out === '' ? '0' : $out;
    }
}
