<?php

namespace Webrek\HealthUi\Checks;

use RuntimeException;
use Webrek\HealthUi\Contracts\Check;
use Webrek\HealthUi\Result;

class DiskSpaceCheck implements Check
{
    public function __construct(
        private readonly string $path,
        private readonly int $warningThreshold = 80,
        private readonly int $failureThreshold = 90,
    ) {}

    public function name(): string
    {
        return 'disk_space';
    }

    public function run(): Result
    {
        $free = @disk_free_space($this->path);
        $total = @disk_total_space($this->path);

        if ($free === false || $total === false || $total <= 0) {
            throw new RuntimeException("Unable to read disk space for [{$this->path}].");
        }

        $usedPercent = (int) round((1 - $free / $total) * 100);
        $meta = ['used_percent' => $usedPercent, 'path' => $this->path];
        $message = "Disk is {$usedPercent}% full.";

        if ($usedPercent >= $this->failureThreshold) {
            return Result::failed($this->name(), $message, $meta);
        }

        if ($usedPercent >= $this->warningThreshold) {
            return Result::warning($this->name(), $message, $meta);
        }

        return Result::ok($this->name(), $message, $meta);
    }
}
