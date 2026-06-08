<?php

namespace Webrek\HealthUi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Webrek\HealthUi\HealthReport;
use Webrek\HealthUi\Result;
use Webrek\HealthUi\Status;

class StatusTest extends TestCase
{
    public function test_severity_ordering(): void
    {
        $this->assertLessThan(Status::Warning->severity(), Status::Ok->severity());
        $this->assertLessThan(Status::Failed->severity(), Status::Warning->severity());
    }

    public function test_report_aggregates_to_the_worst_status(): void
    {
        $report = HealthReport::fromResults([
            Result::ok('a'),
            Result::failed('b'),
            Result::warning('c'),
        ]);

        $this->assertSame(Status::Failed, $report->status);
    }

    public function test_an_empty_report_is_ok(): void
    {
        $this->assertSame(Status::Ok, HealthReport::fromResults([])->status);
    }
}
