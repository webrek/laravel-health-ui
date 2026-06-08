<?php

namespace Webrek\HealthUi\Checks;

use Webrek\HealthUi\Contracts\Check;
use Webrek\HealthUi\Result;

/**
 * Warns when debug mode is enabled — a common and dangerous production misstep.
 */
class DebugModeCheck implements Check
{
    public function name(): string
    {
        return 'debug_mode';
    }

    public function run(): Result
    {
        if ((bool) config('app.debug') === true) {
            return Result::warning($this->name(), 'Debug mode is enabled. Disable it in production.');
        }

        return Result::ok($this->name(), 'Debug mode is disabled.');
    }
}
