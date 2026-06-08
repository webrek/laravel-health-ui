<?php

namespace Webrek\HealthUi\Tests\Feature;

use Webrek\HealthUi\Tests\TestCase;

class EndpointTest extends TestCase
{
    public function test_json_endpoint_is_200_when_healthy(): void
    {
        config(['health-ui.checks' => ['database' => ['enabled' => true]]]);

        $this->getJson('/health')
            ->assertOk()
            ->assertJson(['status' => 'ok', 'healthy' => true])
            ->assertJsonStructure(['status', 'healthy', 'checks' => [['name', 'status', 'message', 'duration_ms']]]);
    }

    public function test_json_endpoint_is_503_when_unhealthy(): void
    {
        config(['health-ui.checks' => [
            'disk_space' => ['enabled' => true, 'path' => sys_get_temp_dir(), 'warning_threshold' => 0, 'failure_threshold' => 0],
        ]]);

        $this->getJson('/health')
            ->assertStatus(503)
            ->assertJson(['status' => 'failed', 'healthy' => false]);
    }

    public function test_status_page_renders_html(): void
    {
        config(['health-ui.checks' => ['database' => ['enabled' => true]]]);

        $this->get('/health')
            ->assertOk()
            ->assertSee('Operational')
            ->assertSee('database');
    }
}
