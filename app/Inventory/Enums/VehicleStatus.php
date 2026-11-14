<?php

namespace App\Inventory\Enums;

enum VehicleStatus: string
{
    case InStock = 'in_stock';
    case Reserved = 'reserved';
    case Sold = 'sold';

    /**
     * The label shown beside the status dot.
     */
    public function label(): string
    {
        return match ($this) {
            self::InStock => 'In stock',
            self::Reserved => 'Reserved',
            self::Sold => 'Sold',
        };
    }
}
