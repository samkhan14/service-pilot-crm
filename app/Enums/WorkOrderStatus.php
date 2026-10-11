<?php

namespace App\Enums;

enum WorkOrderStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Paused = 'paused';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Invoiced = 'invoiced';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Scheduled => 'Scheduled',
            self::InProgress => 'In progress',
            self::Paused => 'Paused',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Invoiced => 'Invoiced',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, match ($this) {
            self::Draft => [self::Scheduled, self::InProgress, self::Cancelled],
            self::Scheduled => [self::InProgress, self::Scheduled, self::Cancelled],
            self::InProgress => [self::Paused, self::Completed, self::Cancelled],
            self::Paused => [self::InProgress, self::Completed, self::Cancelled],
            self::Completed => [self::Invoiced],
            self::Cancelled, self::Invoiced => [],
        }, true);
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Draft, self::Scheduled, self::InProgress, self::Paused], true);
    }
}
