<?php

namespace Webrek\HealthUi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Webrek\HealthUi\Result;
use Webrek\HealthUi\Status;

class ResultTest extends TestCase
{
    public function test_factories_set_the_status(): void
    {
        $this->assertSame(Status::Ok, Result::ok('a')->status);
        $this->assertSame(Status::Warning, Result::warning('a')->status);
        $this->assertSame(Status::Failed, Result::failed('a')->status);
    }

    public function test_with_duration_returns_a_new_instance(): void
    {
        $result = Result::ok('a');
        $timed = $result->withDuration(12.5);

        $this->assertSame(0.0, $result->durationMs);
        $this->assertSame(12.5, $timed->durationMs);
    }

    public function test_to_array(): void
    {
        $array = Result::warning('disk', 'almost full', ['used_percent' => 85])->withDuration(3.14159)->toArray();

        $this->assertSame([
            'name' => 'disk',
            'status' => 'warning',
            'message' => 'almost full',
            'meta' => ['used_percent' => 85],
            'duration_ms' => 3.14,
        ], $array);
    }
}
