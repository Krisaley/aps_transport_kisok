<?php

namespace App\Enums;

enum MovementStatus: string
{
    case Draft = 'draft';
    case AwaitingSchedule = 'awaiting_schedule';
    case Scheduled = 'scheduled';
    case Assigned = 'assigned';
    case EnRoute = 'en_route';
    case OnSite = 'on_site';
    case Collected = 'collected';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case OnHold = 'on_hold';

    public function label(): string
    {
        return match ($this) {
            self::EnRoute => 'En route',
            self::OnSite => 'On site',
            self::AwaitingSchedule => 'Awaiting schedule',
            default => str($this->value)->headline()->toString(),
        };
    }

    /** @return list<self> */
    public function next(): array
    {
        return match ($this) {
            self::Draft => [self::AwaitingSchedule, self::Cancelled],
            self::AwaitingSchedule => [self::Scheduled, self::OnHold, self::Cancelled],
            self::Scheduled => [self::Assigned, self::AwaitingSchedule, self::OnHold, self::Cancelled],
            self::Assigned => [self::EnRoute, self::AwaitingSchedule, self::OnHold, self::Cancelled],
            self::EnRoute => [self::OnSite, self::OnHold],
            self::OnSite => [self::Collected, self::Completed, self::OnHold],
            self::Collected => [self::Completed, self::OnHold],
            self::OnHold => [self::AwaitingSchedule, self::Cancelled],
            self::Completed, self::Cancelled => [],
        };
    }

    public function locksRouting(): bool
    {
        return in_array($this, [self::Scheduled, self::Assigned, self::EnRoute, self::OnSite, self::Collected, self::Completed], true);
    }
}
