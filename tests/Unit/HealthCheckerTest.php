<?php

namespace Webrek\HealthUi\Tests\Unit;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use PHPUnit\Framework\TestCase;
use Webrek\HealthUi\HealthChecker;
use Webrek\HealthUi\Status;
use Webrek\HealthUi\Tests\Support\StubCheck;

class HealthCheckerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        StubCheck::reset();
    }

    public function test_all_ok_is_healthy(): void
    {
        $report = (new HealthChecker([
            new StubCheck('a', Status::Ok),
            new StubCheck('b', Status::Ok),
        ]))->run();

        $this->assertSame(Status::Ok, $report->status);
        $this->assertTrue($report->isHealthy());
        $this->assertCount(2, $report->results);
    }

    public function test_a_warning_degrades_but_stays_healthy(): void
    {
        $report = (new HealthChecker([
            new StubCheck('a', Status::Ok),
            new StubCheck('b', Status::Warning),
        ]))->run();

        $this->assertSame(Status::Warning, $report->status);
        $this->assertTrue($report->isHealthy());
    }

    public function test_a_failure_makes_it_unhealthy(): void
    {
        $report = (new HealthChecker([
            new StubCheck('a', Status::Warning),
            new StubCheck('b', Status::Failed),
        ]))->run();

        $this->assertSame(Status::Failed, $report->status);
        $this->assertFalse($report->isHealthy());
    }

    public function test_a_throwing_check_becomes_a_failure(): void
    {
        $report = (new HealthChecker([new StubCheck('explodes', Status::Ok, throws: true)]))->run();

        $this->assertSame(Status::Failed, $report->status);
        $this->assertSame('explodes', $report->results[0]->name);
        $this->assertSame('boom', $report->results[0]->message);
    }

    public function test_results_carry_a_duration(): void
    {
        $report = (new HealthChecker([new StubCheck('a')]))->run();

        $this->assertGreaterThanOrEqual(0.0, $report->results[0]->durationMs);
    }

    public function test_register_appends_a_check(): void
    {
        $checker = (new HealthChecker)->register(new StubCheck('a'));

        $this->assertCount(1, $checker->checks());
    }

    public function test_results_are_cached_when_a_ttl_is_set(): void
    {
        $cache = new Repository(new ArrayStore);
        $checker = new HealthChecker([new StubCheck('a')], $cache, 60, 'report');

        $checker->run();
        $checker->run();

        $this->assertSame(1, StubCheck::$runs);
    }

    public function test_results_run_every_time_without_a_ttl(): void
    {
        $checker = new HealthChecker([new StubCheck('a')]);

        $checker->run();
        $checker->run();

        $this->assertSame(2, StubCheck::$runs);
    }
}
