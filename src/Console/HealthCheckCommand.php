<?php

namespace Webrek\HealthUi\Console;

use Illuminate\Console\Command;
use Webrek\HealthUi\HealthChecker;
use Webrek\HealthUi\Result;

class HealthCheckCommand extends Command
{
    protected $signature = 'health:check';

    protected $description = 'Run all health checks and report the result';

    public function handle(HealthChecker $checker): int
    {
        $report = $checker->run();

        $this->table(
            ['Check', 'Status', 'Message', 'ms'],
            array_map(fn (Result $r): array => [
                $r->name,
                strtoupper($r->status->value),
                $r->message,
                number_format($r->durationMs, 1),
            ], $report->results),
        );

        $line = "Overall status: {$report->status->label()}";

        if ($report->isHealthy()) {
            $this->info($line);

            return self::SUCCESS;
        }

        $this->error($line);

        return self::FAILURE;
    }
}
