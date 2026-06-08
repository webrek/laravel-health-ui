# Laravel Health UI

[![Latest Version on Packagist](https://img.shields.io/packagist/v/webrek/laravel-health-ui.svg?style=flat-square)](https://packagist.org/packages/webrek/laravel-health-ui)
[![Total Downloads](https://img.shields.io/packagist/dt/webrek/laravel-health-ui.svg?style=flat-square)](https://packagist.org/packages/webrek/laravel-health-ui)
[![Tests](https://img.shields.io/github/actions/workflow/status/webrek/laravel-health-ui/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/webrek/laravel-health-ui/actions/workflows/tests.yml)
[![PHP Version](https://img.shields.io/packagist/php-v/webrek/laravel-health-ui.svg?style=flat-square)](https://php.net)
[![License](https://img.shields.io/packagist/l/webrek/laravel-health-ui.svg?style=flat-square)](LICENSE)

A production health dashboard for Laravel. Pluggable checks (database, cache,
disk, external services), a JSON endpoint your uptime monitor can poll, and a
clean status page — all from one route.

## Quickstart

```bash
composer require webrek/laravel-health-ui
```

Visit `/health` for the status page, or poll it as JSON:

```bash
curl -H "Accept: application/json" https://your-app.test/health
```

```json
{
  "status": "ok",
  "healthy": true,
  "checks": [
    {"name": "database", "status": "ok", "message": "Connection [sqlite] is reachable.", "meta": [], "duration_ms": 0.4},
    {"name": "cache", "status": "ok", "message": "Cache read/write succeeded.", "meta": [], "duration_ms": 0.2},
    {"name": "disk_space", "status": "ok", "message": "Disk is 41% full.", "meta": {"used_percent": 41}, "duration_ms": 0.1}
  ]
}
```

The endpoint returns **200** while the app is healthy and **503** the moment a
check hard-fails — exactly what an uptime monitor (Pingdom, UptimeRobot, a
Kubernetes liveness probe) needs.

## What you get over Laravel's `/up`

Laravel's built-in `/up` route answers a single question: did the framework
boot? That tells you nothing about whether your database is reachable, your
cache is responding, the disk is filling up, or a third-party API you depend on
is down. This package runs real checks, aggregates them, and shows you which one
is unhealthy — on a status page and as machine-readable JSON.

## Status model

Each check returns one of three statuses, and the overall status is the worst of
them:

| Status | Meaning | HTTP |
| --- | --- | --- |
| `ok` (Operational) | Healthy | 200 |
| `warning` (Degraded) | Working but needs attention (e.g. disk 85% full, debug mode on) | 200 |
| `failed` (Down) | A hard failure | 503 |

Warnings keep the endpoint at **200** so you are not paged for degraded-but-
serving conditions — only hard failures flip it to **503**.

## Built-in checks

Enable and tune them in `config/health-ui.php`:

```php
'checks' => [
    'database'   => ['enabled' => true, 'connection' => null],
    'cache'      => ['enabled' => true, 'store' => null],
    'disk_space' => ['enabled' => true, 'path' => null, 'warning_threshold' => 80, 'failure_threshold' => 90],
    'debug_mode' => ['enabled' => true],
    'http'       => ['enabled' => true, 'endpoints' => [
        ['name' => 'Payments API', 'url' => 'https://api.example.com/health', 'timeout' => 5],
    ]],
],
```

- **database** — runs `select 1` on the connection.
- **cache** — writes and reads back a value.
- **disk_space** — warns/fails past the configured used-percentage thresholds.
- **debug_mode** — warns when `APP_DEBUG` is on (a frequent production slip).
- **http** — pings each external dependency and expects a 2xx.

## Writing your own check

Implement the `Check` contract. Return a `Result`, or just throw — the checker
turns an exception into a failed result automatically.

```php
use Webrek\HealthUi\Contracts\Check;
use Webrek\HealthUi\Result;

class RedisQueueDepthCheck implements Check
{
    public function name(): string
    {
        return 'queue_depth';
    }

    public function run(): Result
    {
        $depth = Redis::llen('queues:default');

        return $depth < 1000
            ? Result::ok($this->name(), "Queue depth is {$depth}.", ['depth' => $depth])
            : Result::warning($this->name(), "Queue is backing up ({$depth}).", ['depth' => $depth]);
    }
}
```

Register it (e.g. in a service provider):

```php
app(\Webrek\HealthUi\HealthChecker::class)->register(new RedisQueueDepthCheck);
```

## Command line

Run the checks from the CLI or a cron — it exits non-zero when unhealthy, so it
plugs straight into deploy gates and alerting:

```bash
php artisan health:check
```

## Configuration highlights

```php
'route' => env('HEALTH_UI_ROUTE', 'health'),

// Protect the endpoint — it can expose internal details.
'middleware' => [],

// Cache the report so frequent polls don't run every check each hit.
'cache' => ['ttl' => 0, 'store' => null, 'key' => 'health-ui.report'],
```

Publish the config and views to customise them:

```bash
php artisan vendor:publish --tag=health-ui-config
php artisan vendor:publish --tag=health-ui-views
```

> The endpoint can reveal internal state. In production, put it behind
> `middleware` (a token, a signed URL, or an internal-network restriction).

## Requirements

| Component | Version |
| --------- | ------- |
| PHP | 8.2+ |
| Laravel | 12.x |

## Testing

```bash
composer install
composer test
```

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

Please review the [security policy](SECURITY.md) before reporting a
vulnerability.

## License

The MIT License (MIT). See [LICENSE](LICENSE).
