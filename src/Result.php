<?php

namespace Webrek\HealthUi;

/**
 * The outcome of a single health check.
 */
final class Result
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string $name,
        public readonly Status $status,
        public readonly string $message = '',
        public readonly array $meta = [],
        public readonly float $durationMs = 0.0,
    ) {}

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function ok(string $name, string $message = '', array $meta = []): self
    {
        return new self($name, Status::Ok, $message, $meta);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function warning(string $name, string $message = '', array $meta = []): self
    {
        return new self($name, Status::Warning, $message, $meta);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function failed(string $name, string $message = '', array $meta = []): self
    {
        return new self($name, Status::Failed, $message, $meta);
    }

    public function withDuration(float $durationMs): self
    {
        return new self($this->name, $this->status, $this->message, $this->meta, $durationMs);
    }

    /**
     * @return array{name: string, status: string, message: string, meta: array<string, mixed>, duration_ms: float}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'status' => $this->status->value,
            'message' => $this->message,
            'meta' => $this->meta,
            'duration_ms' => round($this->durationMs, 2),
        ];
    }
}
