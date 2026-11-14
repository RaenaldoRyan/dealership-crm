<?php

namespace App\Core\Enums;

enum ActivityType: string
{
    case ContactCreated = 'contact_created';
    case DealCreated = 'deal_created';
    case StageChanged = 'stage_changed';
    case NoteAdded = 'note_added';
    case VehicleAdded = 'vehicle_added';
    case VehicleLinked = 'vehicle_linked';
    case DealSold = 'deal_sold';
}
