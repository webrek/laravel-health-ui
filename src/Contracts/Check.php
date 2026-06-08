<?php

namespace Webrek\HealthUi\Contracts;

use Webrek\HealthUi\Result;

interface Check
{
    /**
     * A short, stable identifier for the check.
     */
    public function name(): string;

    /**
     * Run the check. Throwing is treated as a failure by the checker, so you
     * may either return a Result or let an exception bubble up.
     */
    public function run(): Result;
}
