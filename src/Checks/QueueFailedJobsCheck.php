<?php

namespace Webrek\HealthUi\Checks;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webrek\HealthUi\Contracts\Check;
use Webrek\HealthUi\Result;

/**
 * Watches the failed-jobs table and warns/fails as the backlog grows.
 */
class QueueFailedJobsCheck implements Check
{
    public function __construct(
        private readonly ?string $connection = null,
        private readonly string $table = 'failed_jobs',
        private readonly int $warningThreshold = 1,
        private readonly int $failureThreshold = 25,
    ) {}

    public function name(): string
    {
        return 'queue_failed_jobs';
    }

    public function run(): Result
    {
        if (! Schema::connection($this->connection)->hasTable($this->table)) {
            return Result::ok($this->name(), "No [{$this->table}] table; failed jobs are not tracked.");
        }

        $count = DB::connection($this->connection)->table($this->table)->count();
        $meta = ['failed' => $count];
        $message = "{$count} failed job(s).";

        if ($count >= $this->failureThreshold) {
            return Result::failed($this->name(), $message, $meta);
        }

        if ($count >= $this->warningThreshold) {
            return Result::warning($this->name(), $message, $meta);
        }

        return Result::ok($this->name(), $message, $meta);
    }
}
