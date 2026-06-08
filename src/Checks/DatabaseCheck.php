<?php

namespace Webrek\HealthUi\Checks;

use Illuminate\Support\Facades\DB;
use Webrek\HealthUi\Contracts\Check;
use Webrek\HealthUi\Result;

class DatabaseCheck implements Check
{
    public function __construct(private readonly ?string $connection = null) {}

    public function name(): string
    {
        return 'database';
    }

    public function run(): Result
    {
        $connection = DB::connection($this->connection);
        $connection->select('select 1');

        return Result::ok($this->name(), "Connection [{$connection->getName()}] is reachable.");
    }
}
