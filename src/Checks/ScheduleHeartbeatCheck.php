<?php

namespace Webrek\HealthUi\Checks;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Webrek\HealthUi\Contracts\Check;
use Webrek\HealthUi\Result;

/**
 * Confirms the scheduler is alive. Have a frequent task write a heartbeat:
 *
 *     $schedule->call(fn () => cache()->forever('health-ui:schedule-heartbeat', now()->timestamp))
 *         ->everyMinute();
 *
 * This check then fails if that heartbeat goes stale.
 */
class ScheduleHeartbeatCheck implements Check
{
    public function __construct(
        private readonly string $key = 'health-ui:schedule-heartbeat',
        private readonly int $maxAgeMinutes = 5,
        private readonly ?string $store = null,
    ) {}

    public function name(): string
    {
        return 'schedule';
    }

    public function run(): Result
    {
        $last = Cache::store($this->store)->get($this->key);

        if ($last === null) {
            return Result::warning($this->name(), 'No scheduler heartbeat has been recorded yet.');
        }

        $ageMinutes = (int) abs(now()->diffInMinutes(Carbon::createFromTimestamp((int) $last)));
        $meta = ['age_minutes' => $ageMinutes];
        $message = "Scheduler last ran {$ageMinutes} minute(s) ago.";

        if ($ageMinutes > $this->maxAgeMinutes) {
            return Result::failed($this->name(), $message, $meta);
        }

        return Result::ok($this->name(), $message, $meta);
    }
}
