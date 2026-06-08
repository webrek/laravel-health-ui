<?php

namespace Webrek\HealthUi\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webrek\HealthUi\Checks\CertificateExpiryCheck;
use Webrek\HealthUi\Checks\PendingMigrationsCheck;
use Webrek\HealthUi\Checks\QueueFailedJobsCheck;
use Webrek\HealthUi\Checks\ScheduleHeartbeatCheck;
use Webrek\HealthUi\Status;
use Webrek\HealthUi\Tests\TestCase;

class ExtraChecksTest extends TestCase
{
    public function test_queue_failed_jobs_thresholds(): void
    {
        Schema::create('failed_jobs', function (Blueprint $table): void {
            $table->id();
            $table->text('payload')->nullable();
        });

        $check = new QueueFailedJobsCheck(warningThreshold: 1, failureThreshold: 3);

        $this->assertSame(Status::Ok, $check->run()->status);

        DB::table('failed_jobs')->insert(['payload' => 'x']);
        $this->assertSame(Status::Warning, $check->run()->status);

        DB::table('failed_jobs')->insert([['payload' => 'x'], ['payload' => 'y']]);
        $this->assertSame(Status::Failed, $check->run()->status);
    }

    public function test_queue_failed_jobs_is_ok_without_a_table(): void
    {
        $this->assertSame(Status::Ok, (new QueueFailedJobsCheck)->run()->status);
    }

    public function test_schedule_heartbeat(): void
    {
        $check = new ScheduleHeartbeatCheck('hb', 5);

        $this->assertSame(Status::Warning, $check->run()->status);

        Cache::forever('hb', now()->timestamp);
        $this->assertSame(Status::Ok, $check->run()->status);

        Cache::forever('hb', now()->subMinutes(30)->timestamp);
        $this->assertSame(Status::Failed, $check->run()->status);
    }

    public function test_certificate_expiry_thresholds(): void
    {
        $ok = new CertificateExpiryCheck('example.com', 14, 3, 5, fn (): int => now()->addDays(30)->timestamp);
        $warn = new CertificateExpiryCheck('example.com', 14, 3, 5, fn (): int => now()->addDays(10)->timestamp);
        $fail = new CertificateExpiryCheck('example.com', 14, 3, 5, fn (): int => now()->addDay()->timestamp);

        $this->assertSame(Status::Ok, $ok->run()->status);
        $this->assertSame(Status::Warning, $warn->run()->status);
        $this->assertSame(Status::Failed, $fail->run()->status);
    }

    public function test_pending_migrations_warns_without_a_repository(): void
    {
        $this->assertSame(Status::Warning, (new PendingMigrationsCheck)->run()->status);
    }

    public function test_pending_migrations_ok_when_repository_is_current(): void
    {
        $this->artisan('migrate');

        $this->assertSame(Status::Ok, (new PendingMigrationsCheck)->run()->status);
    }
}
