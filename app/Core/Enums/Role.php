<?php

namespace App\Core\Enums;

enum Role: string
{
    case Rep = 'rep';
    case Manager = 'manager';

    /**
     * The label shown on the staff list and forms.
     */
    public function label(): string
    {
        return match ($this) {
            self::Rep => 'Sales rep',
            self::Manager => 'Manager',
        };
    }
}
