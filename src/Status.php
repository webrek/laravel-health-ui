<?php

namespace Webrek\HealthUi;

enum Status: string
{
    case Ok = 'ok';
    case Warning = 'warning';
    case Failed = 'failed';

    /**
     * Higher is worse, so the overall status is the maximum across checks.
     */
    public function severity(): int
    {
        return match ($this) {
            self::Ok => 0,
            self::Warning => 1,
            self::Failed => 2,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Ok => 'Operational',
            self::Warning => 'Degraded',
            self::Failed => 'Down',
        };
    }
}
