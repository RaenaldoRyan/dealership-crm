<?php

namespace App\Sales\Enums;

enum DealStage: string
{
    case Enquiry = 'enquiry';
    case TestDrive = 'test_drive';
    case Negotiation = 'negotiation';
    case Sold = 'sold';
    case Lost = 'lost';

    /**
     * The label shown in the deal list and forms.
     */
    public function label(): string
    {
        return match ($this) {
            self::Enquiry => 'Enquiry',
            self::TestDrive => 'Test drive',
            self::Negotiation => 'Negotiation',
            self::Sold => 'Sold',
            self::Lost => 'Lost',
        };
    }
}
