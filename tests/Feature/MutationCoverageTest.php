<?php

namespace Webrek\HealthUi\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Webrek\HealthUi\Checks\CertificateExpiryCheck;
use Webrek\HealthUi\Checks\DiskSpaceCheck;
use Webrek\HealthUi\Checks\HttpCheck;
use Webrek\HealthUi\Checks\QueueFailedJobsCheck;
use Webrek\HealthUi\Checks\ScheduleHeartbeatCheck;
use Webrek\HealthUi\Tests\TestCase;

class MutationCoverageTest extends TestCase
{
    public function test_disk_space_reports_message_and_meta(): void
    {
        $result = (new DiskSpaceCheck(sys_get_temp_dir(), 101, 102))->run();

        $this->assertStringContainsString('full', $result->message);
        $this->assertArrayHasKey('used_percent', $result->meta);
        $this->assertSame(sys_get_temp_dir(), $result->meta['path']);
        $this->assertIsInt($result->meta['used_percent']);
    }

    public function test_disk_space_throws_for_an_unreadable_path(): void
    {
        $this->expectException(RuntimeException::class);

        (new DiskSpaceCheck('/no/such/path/xyz-12345', 80, 90))->run();
    }

    public function test_http_check_reports_meta(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        $result = (new HttpCheck('Payments', 'https://example.test/health'))->run();

        $this->assertSame(200, $result->meta['status']);
        $this->assertSame('https://example.test/health', $result->meta['url']);
        $this->assertStringContainsString('Payments', $result->message);
        $this->assertSame('http:payments', $result->name);
    }

    public function test_schedule_reports_age_and_message(): void
    {
        Cache::forever('hb', now()->subMinutes(2)->timestamp);

        $result = (new ScheduleHeartbeatCheck('hb', 5))->run();

        $this->assertSame(2, $result->meta['age_minutes']);
        $this->assertStringContainsString('ago', $result->message);
    }

    public function test_certificate_reports_host_and_days(): void
    {
        // One hour of slack so the floor() in the check lands cleanly on 30.
        $result = (new CertificateExpiryCheck('example.com', 14, 3, 5, fn (): int => now()->addDays(30)->addHour()->timestamp))->run();

        $this->assertSame('example.com', $result->meta['host']);
        $this->assertSame(30, $result->meta['days_until_expiry']);
        $this->assertSame('certificate:examplecom', $result->name);
    }

    public function test_queue_failed_jobs_reports_count(): void
    {
        Schema::create('failed_jobs', fn (Blueprint $table) => $table->id());

        $result = (new QueueFailedJobsCheck)->run();

        $this->assertSame(0, $result->meta['failed']);
        $this->assertStringContainsString('0 failed', $result->message);
    }

    public function test_command_prints_the_check_rows(): void
    {
        config(['health-ui.checks' => ['database' => ['enabled' => true]]]);

        $this->artisan('health:check')
            ->expectsOutputToContain('database')
            ->expectsOutputToContain('Operational')
            ->assertSuccessful();
    }
}
