# Laravel Health UI

[![Última versión en Packagist](https://img.shields.io/packagist/v/webrek/laravel-health-ui.svg?style=flat-square)](https://packagist.org/packages/webrek/laravel-health-ui)
[![Descargas totales](https://img.shields.io/packagist/dt/webrek/laravel-health-ui.svg?style=flat-square)](https://packagist.org/packages/webrek/laravel-health-ui)
[![Pruebas](https://img.shields.io/github/actions/workflow/status/webrek/laravel-health-ui/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/webrek/laravel-health-ui/actions/workflows/tests.yml)
[![Versión de PHP](https://img.shields.io/packagist/php-v/webrek/laravel-health-ui.svg?style=flat-square)](https://php.net)
[![Licencia](https://img.shields.io/packagist/l/webrek/laravel-health-ui.svg?style=flat-square)](LICENSE)

Un panel de salud de producción para Laravel. Verificaciones conectables (base de
datos, caché, disco, servicios externos), un endpoint JSON que tu monitor de
uptime puede sondear y una página de estado limpia — todo desde una sola ruta.

## Quickstart

```bash
composer require webrek/laravel-health-ui
```

Visita `/health` para ver la página de estado, o sondéala como JSON:

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

El endpoint devuelve **200** mientras la app está sana y **503** en el momento en
que una verificación falla de forma dura — exactamente lo que necesita un monitor
de uptime (Pingdom, UptimeRobot, una sonda de liveness de Kubernetes).

## Lo que obtienes frente al `/up` de Laravel

La ruta `/up` integrada en Laravel responde una sola pregunta: ¿arrancó el
framework? Eso no te dice nada sobre si tu base de datos es alcanzable, si tu
caché está respondiendo, si el disco se está llenando o si una API de terceros de
la que dependes está caída. Este paquete ejecuta verificaciones reales, las
agrega y te muestra cuál está enferma — en una página de estado y como JSON
legible por máquina.

## Modelo de estado

Cada verificación devuelve uno de tres estados, y el estado general es el peor de
ellos:

| Estado | Significado | HTTP |
| --- | --- | --- |
| `ok` (Operativo) | Sano | 200 |
| `warning` (Degradado) | Funciona pero requiere atención (p. ej. disco al 85%, modo debug activado) | 200 |
| `failed` (Caído) | Una falla dura | 503 |

Las advertencias mantienen el endpoint en **200** para que no te alerten por
condiciones degradadas-pero-sirviendo — solo las fallas duras lo cambian a
**503**.

## Verificaciones integradas

Actívalas y ajústalas en `config/health-ui.php`:

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

- **database** — ejecuta `select 1` en la conexión.
- **cache** — escribe y vuelve a leer un valor.
- **disk_space** — advierte/falla al pasar los umbrales de porcentaje usado configurados.
- **debug_mode** — advierte cuando `APP_DEBUG` está activado (un desliz frecuente en producción).
- **http** — hace ping a cada dependencia externa y espera un 2xx.
- **queue_failed_jobs** — advierte/falla a medida que crece el backlog de trabajos fallidos.
- **schedule** — falla cuando el heartbeat del programador se vuelve obsoleto (ver abajo).
- **migrations** — advierte cuando hay migraciones confirmadas pero no ejecutadas aquí.
- **certificates** — advierte antes de que expire el certificado TLS de un host.

### Heartbeat del programador

La verificación `schedule` lee una marca de tiempo que tu programador mantiene
fresca. Agrega una tarea frecuente que la estampe:

```php
// routes/console.php (or your schedule definition)
Schedule::call(fn () => cache()->forever('health-ui:schedule-heartbeat', now()->timestamp))
    ->everyMinute();
```

La verificación entonces falla si ese heartbeat es más antiguo que
`max_age_minutes`.

## Cómo escribir tu propia verificación

Implementa el contrato `Check`. Devuelve un `Result`, o simplemente lanza una
excepción — el verificador convierte una excepción en un resultado fallido
automáticamente.

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

Regístrala (p. ej. en un service provider):

```php
app(\Webrek\HealthUi\HealthChecker::class)->register(new RedisQueueDepthCheck);
```

## Línea de comandos

Ejecuta las verificaciones desde la CLI o un cron — termina con código distinto
de cero cuando está enfermo, así que se conecta directamente a los gates de
despliegue y a las alertas:

```bash
php artisan health:check
```

## Aspectos destacados de la configuración

```php
'route' => env('HEALTH_UI_ROUTE', 'health'),

// Protect the endpoint — it can expose internal details.
'middleware' => [],

// Cache the report so frequent polls don't run every check each hit.
'cache' => ['ttl' => 0, 'store' => null, 'key' => 'health-ui.report'],
```

Publica la configuración y las vistas para personalizarlas:

```bash
php artisan vendor:publish --tag=health-ui-config
php artisan vendor:publish --tag=health-ui-views
```

> El endpoint puede revelar estado interno. En producción, ponlo detrás de
> `middleware` (un token, una URL firmada o una restricción de red interna).

## Requisitos

| Componente | Versión |
| --------- | ------- |
| PHP | 8.2+ |
| Laravel | 12.x / 13.x |

## Pruebas

```bash
composer install
composer test
```

## Contribuir

Consulta [CONTRIBUTING.md](CONTRIBUTING.md).

## Seguridad

Por favor revisa la [política de seguridad](SECURITY.md) antes de reportar una
vulnerabilidad.

## Licencia

La Licencia MIT (MIT). Consulta [LICENSE](LICENSE).
