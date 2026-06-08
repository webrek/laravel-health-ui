<?php

namespace Webrek\HealthUi\Tests\Feature;

use Webrek\HealthUi\Tests\TestCase;

class CommandTest extends TestCase
{
    public function test_it_exits_zero_when_healthy(): void
    {
        config(['health-ui.checks' => ['database' => ['enabled' => true]]]);

        $this->artisan('health:check')->assertSuccessful();
    }

    public function test_it_exits_non_zero_when_unhealthy(): void
    {
        config(['health-ui.checks' => [
            'disk_space' => ['enabled' => true, 'path' => sys_get_temp_dir(), 'warning_threshold' => 0, 'failure_threshold' => 0],
        ]]);

        $this->artisan('health:check')->assertFailed();
    }
}
