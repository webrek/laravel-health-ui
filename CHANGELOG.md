# Changelog

All notable changes to `webrek/laravel-health-ui` are documented here. The
format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and the
project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `HealthChecker` that runs registered checks, times them, catches failures and
  aggregates an overall status, with optional result caching.
- Built-in checks: database, cache, disk space, debug mode and HTTP endpoints.
- `Check` contract, `Result` and `HealthReport` value objects, and a `Status`
  enum (operational / degraded / down).
- A health route serving JSON (200 / 503) for uptime monitors and an HTML status
  page.
- `health:check` artisan command with a non-zero exit on failure.
- Publishable config and views.
