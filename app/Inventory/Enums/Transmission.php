<?php

namespace App\Inventory\Enums;

enum Transmission: string
{
    case Manual = 'manual';
    case Automatic = 'automatic';

    /**
     * The label shown on the vehicle form and list.
     */
    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Automatic => 'Automatic',
        };
    }
}
