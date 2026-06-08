<?php

namespace Webrek\HealthUi;

/**
 * The aggregated outcome of every registered check.
 */
final class HealthReport
{
    /**
     * @param  list<Result>  $results
     */
    public function __construct(
        public readonly array $results,
        public readonly Status $status,
    ) {}

    /**
     * @param  list<Result>  $results
     */
    public static function fromResults(array $results): self
    {
        $status = Status::Ok;

        foreach ($results as $result) {
            if ($result->status->severity() > $status->severity()) {
                $status = $result->status;
            }
        }

        return new self($results, $status);
    }

    /**
     * Healthy means nothing is hard-down. Warnings still count as healthy, so
     * uptime monitors are not paged for degraded-but-serving conditions.
     */
    public function isHealthy(): bool
    {
        return $this->status !== Status::Failed;
    }

    /**
     * @return array{status: string, healthy: bool, checks: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'healthy' => $this->isHealthy(),
            'checks' => array_map(fn (Result $r): array => $r->toArray(), $this->results),
        ];
    }
}
