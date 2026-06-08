<?php

namespace Webrek\HealthUi\Tests\Support;

use RuntimeException;
use Webrek\HealthUi\Contracts\Check;
use Webrek\HealthUi\Result;
use Webrek\HealthUi\Status;

class StubCheck implements Check
{
    public static int $runs = 0;

    public function __construct(
        private readonly string $name,
        private readonly Status $status = Status::Ok,
        private readonly bool $throws = false,
    ) {}

    public static function reset(): void
    {
        self::$runs = 0;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function run(): Result
    {
        self::$runs++;

        if ($this->throws) {
            throw new RuntimeException('boom');
        }

        return new Result($this->name, $this->status, 'stub');
    }
}
