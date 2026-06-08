<?php

namespace Webrek\HealthUi;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webrek\HealthUi\Checks\CacheCheck;
use Webrek\HealthUi\Checks\DatabaseCheck;
use Webrek\HealthUi\Checks\DebugModeCheck;
use Webrek\HealthUi\Checks\DiskSpaceCheck;
use Webrek\HealthUi\Checks\HttpCheck;
use Webrek\HealthUi\Console\HealthCheckCommand;
use Webrek\HealthUi\Contracts\Check;
use Webrek\HealthUi\Http\HealthController;

class HealthUiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/health-ui.php', 'health-ui');

        $this->app->singleton(HealthChecker::class, function ($app): HealthChecker {
            $config = $app['config']->get('health-ui');
            $ttl = (int) ($config['cache']['ttl'] ?? 0);

            return new HealthChecker(
                $this->makeChecks($config['checks'] ?? []),
                $ttl > 0 ? $app->make(CacheFactory::class)->store($config['cache']['store'] ?? null) : null,
                $ttl,
                $config['cache']['key'] ?? 'health-ui.report',
            );
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'health-ui');

        Route::middleware($this->app['config']->get('health-ui.middleware', []))
            ->get($this->app['config']->get('health-ui.route', 'health'), HealthController::class)
            ->name('health-ui');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/health-ui.php' => $this->app->configPath('health-ui.php'),
            ], 'health-ui-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => $this->app->resourcePath('views/vendor/health-ui'),
            ], 'health-ui-views');

            $this->commands([HealthCheckCommand::class]);
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $config
     * @return list<Check>
     */
    private function makeChecks(array $config): array
    {
        $checks = [];

        if ($config['database']['enabled'] ?? false) {
            $checks[] = new DatabaseCheck($config['database']['connection'] ?? null);
        }

        if ($config['cache']['enabled'] ?? false) {
            $checks[] = new CacheCheck($config['cache']['store'] ?? null);
        }

        if ($config['disk_space']['enabled'] ?? false) {
            $checks[] = new DiskSpaceCheck(
                $config['disk_space']['path'] ?? base_path(),
                (int) ($config['disk_space']['warning_threshold'] ?? 80),
                (int) ($config['disk_space']['failure_threshold'] ?? 90),
            );
        }

        if ($config['debug_mode']['enabled'] ?? false) {
            $checks[] = new DebugModeCheck;
        }

        if ($config['http']['enabled'] ?? false) {
            foreach ($config['http']['endpoints'] ?? [] as $endpoint) {
                $checks[] = new HttpCheck(
                    $endpoint['name'],
                    $endpoint['url'],
                    (int) ($endpoint['timeout'] ?? 5),
                );
            }
        }

        return $checks;
    }
}
