<?php

namespace App\Support;

/**
 * Conversiones entre días decimales y partes (días, horas, minutos).
 *
 * Jornada: 8 horas = 480 minutos = 1 día.
 */
final class VacacionesTiempo
{
    public const MINUTOS_POR_DIA = 480;

    public const HORAS_POR_DIA = 8;

    /**
     * Convierte días + horas + minutos a días decimales.
     */
    public static function aDias(int $dias, int $horas, int $minutos, int $precision = 4): float
    {
        return round($dias + ($horas + $minutos / 60) / self::HORAS_POR_DIA, $precision);
    }

    /**
     * Descompone días decimales en [dias, horas, minutos].
     *
     * @return array{dias: int, horas: int, minutos: int}
     */
    public static function aPartesDias(float $dias): array
    {
        $totalMinutos = (int) round($dias * self::MINUTOS_POR_DIA);

        $diasEnteros = intdiv($totalMinutos, self::MINUTOS_POR_DIA);
        $resto = $totalMinutos % self::MINUTOS_POR_DIA;

        return [
            'dias' => $diasEnteros,
            'horas' => intdiv($resto, 60),
            'minutos' => $resto % 60,
        ];
    }

    /**
     * Descompone horas decimales en [horas, minutos].
     *
     * @return array{horas: int, minutos: int}
     */
    public static function aPartesHoras(float $horas): array
    {
        $totalMinutos = (int) round($horas * 60);

        return [
            'horas' => intdiv($totalMinutos, 60),
            'minutos' => $totalMinutos % 60,
        ];
    }

    /**
     * Texto legible de días decimales. Ej: "2 días, 5 horas y 55 minutos".
     */
    public static function aTextoDias(float $dias): ?string
    {
        if ($dias <= 0) {
            return null;
        }

        $partes = self::aPartesDias($dias);

        return self::unirPartes([
            $partes['dias'] > 0 ? $partes['dias'].' '.($partes['dias'] === 1 ? 'día' : 'días') : null,
            $partes['horas'] > 0 ? $partes['horas'].' '.($partes['horas'] === 1 ? 'hora' : 'horas') : null,
            $partes['minutos'] > 0 ? $partes['minutos'].' '.($partes['minutos'] === 1 ? 'minuto' : 'minutos') : null,
        ]);
    }

    /**
     * Texto legible de horas decimales. Ej: "5 horas y 30 minutos".
     */
    public static function aTextoHoras(float $horas): ?string
    {
        if ($horas <= 0) {
            return null;
        }

        $partes = self::aPartesHoras($horas);

        return self::unirPartes([
            $partes['horas'] > 0 ? $partes['horas'].' '.($partes['horas'] === 1 ? 'hora' : 'horas') : null,
            $partes['minutos'] > 0 ? $partes['minutos'].' '.($partes['minutos'] === 1 ? 'minuto' : 'minutos') : null,
        ]);
    }

    /**
     * @param  array<int, string|null>  $partes
     */
    private static function unirPartes(array $partes): string
    {
        $partes = array_values(array_filter($partes));

        if ($partes === []) {
            return '0 días';
        }

        if (count($partes) === 1) {
            return $partes[0];
        }

        $ultima = array_pop($partes);

        return implode(', ', $partes).' y '.$ultima;
    }
}
