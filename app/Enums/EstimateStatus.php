<?php

namespace App\Enums;

enum EstimateStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, match ($this) {
            self::Draft => [self::Sent, self::Approved, self::Rejected],
            self::Sent => [self::Approved, self::Rejected, self::Expired],
            self::Approved, self::Rejected, self::Expired => [],
        }, true);
    }
}
