<?php

namespace App\Inventory\Support;

class Registration
{
    /**
     * Store and compare plates without spaces, in upper case.
     */
    public static function normalize(string $value): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', $value));
    }

    /**
     * Whether the plate is a current, prefix, suffix, or dateless UK registration.
     */
    public static function isValid(string $value): bool
    {
        return self::style(self::normalize($value)) !== null;
    }

    /**
     * Display form. Unrecognised plates are returned unchanged after normalising.
     */
    public static function format(string $value): string
    {
        $plate = self::normalize($value);

        return match (self::style($plate)) {
            'current', 'prefix' => substr($plate, 0, -3).' '.substr($plate, -3),
            'suffix' => substr($plate, 0, 3).' '.substr($plate, 3),
            'dateless_letters' => preg_replace('/^([A-Z]{1,3})(\d{1,4})$/', '$1 $2', $plate) ?? $plate,
            'dateless_numbers' => preg_replace('/^(\d{1,4})([A-Z]{1,3})$/', '$1 $2', $plate) ?? $plate,
            default => $plate,
        };
    }

    private static function style(string $plate): ?string
    {
        return match (true) {
            preg_match('/^[A-Z]{2}\d{2}[A-Z]{3}$/', $plate) === 1 => 'current',
            preg_match('/^[A-Z]\d{1,3}[A-Z]{3}$/', $plate) === 1 => 'prefix',
            preg_match('/^[A-Z]{3}\d{1,3}[A-Z]$/', $plate) === 1 => 'suffix',
            preg_match('/^[A-Z]{1,3}\d{1,4}$/', $plate) === 1 => 'dateless_letters',
            preg_match('/^\d{1,4}[A-Z]{1,3}$/', $plate) === 1 => 'dateless_numbers',
            default => null,
        };
    }
}
