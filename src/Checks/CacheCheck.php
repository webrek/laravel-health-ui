<?php

namespace Webrek\HealthUi\Checks;

use Illuminate\Support\Facades\Cache;
use Webrek\HealthUi\Contracts\Check;
use Webrek\HealthUi\Result;

class CacheCheck implements Check
{
    public function __construct(private readonly ?string $store = null) {}

    public function name(): string
    {
        return 'cache';
    }

    public function run(): Result
    {
        $store = Cache::store($this->store);
        $value = (string) microtime(true);

        $store->put('health-ui:ping', $value, 10);

        if ($store->get('health-ui:ping') !== $value) {
            return Result::failed($this->name(), 'The cache store did not return the value just written.');
        }

        return Result::ok($this->name(), 'Cache read/write succeeded.');
    }
}
