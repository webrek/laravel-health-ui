<?php

namespace Webrek\HealthUi\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Webrek\HealthUi\Checks\CacheCheck;
use Webrek\HealthUi\Checks\DatabaseCheck;
use Webrek\HealthUi\Checks\DebugModeCheck;
use Webrek\HealthUi\Checks\DiskSpaceCheck;
use Webrek\HealthUi\Checks\HttpCheck;
use Webrek\HealthUi\Status;
use Webrek\HealthUi\Tests\TestCase;

class ChecksTest extends TestCase
{
    public function test_database_check_passes_on_a_reachable_connection(): void
    {
        $this->assertSame(Status::Ok, (new DatabaseCheck)->run()->status);
    }

    public function test_cache_check_passes(): void
    {
        $this->assertSame(Status::Ok, (new CacheCheck)->run()->status);
    }

    public function test_disk_space_thresholds(): void
    {
        $path = sys_get_temp_dir();

        $this->assertSame(Status::Ok, (new DiskSpaceCheck($path, 101, 102))->run()->status);
        $this->assertSame(Status::Warning, (new DiskSpaceCheck($path, 0, 101))->run()->status);
        $this->assertSame(Status::Failed, (new DiskSpaceCheck($path, 0, 0))->run()->status);
    }

    public function test_debug_mode_check(): void
    {
        config(['app.debug' => false]);
        $this->assertSame(Status::Ok, (new DebugModeCheck)->run()->status);

        config(['app.debug' => true]);
        $this->assertSame(Status::Warning, (new DebugModeCheck)->run()->status);
    }

    public function test_http_check(): void
    {
        Http::fakeSequence()->push('', 200)->push('', 503);

        $this->assertSame(Status::Ok, (new HttpCheck('API', 'https://example.test/health'))->run()->status);
        $this->assertSame(Status::Failed, (new HttpCheck('API', 'https://example.test/health'))->run()->status);
    }
}
