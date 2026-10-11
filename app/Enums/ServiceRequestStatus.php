<?php

namespace App\Enums;

enum ServiceRequestStatus: string
{
    case New = 'new';
    case Assigned = 'assigned';
    case Converted = 'converted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Assigned => 'Assigned',
            self::Converted => 'Converted',
            self::Cancelled => 'Cancelled',
        };
    }
}
