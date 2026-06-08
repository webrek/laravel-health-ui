<?php

namespace Webrek\HealthUi;

use Illuminate\Contracts\Cache\Repository as Cache;
use Throwable;
use Webrek\HealthUi\Contracts\Check;

class HealthChecker
{
    /** @var list<Check> */
    private array $checks;

    /**
     * @param  array<Check>  $checks
     */
    public function __construct(
        array $checks = [],
        private readonly ?Cache $cache = null,
        private readonly int $cacheTtl = 0,
        private readonly string $cacheKey = 'health-ui.report',
    ) {
        $this->checks = array_values($checks);
    }

    public function register(Check $check): self
    {
        $this->checks[] = $check;

        return $this;
    }

    /**
     * @return list<Check>
     */
    public function checks(): array
    {
        return $this->checks;
    }

    public function run(): HealthReport
    {
        if ($this->cache !== null && $this->cacheTtl > 0) {
            return $this->cache->remember($this->cacheKey, $this->cacheTtl, fn (): HealthReport => $this->perform());
        }

        return $this->perform();
    }

    private function perform(): HealthReport
    {
        $results = [];

        foreach ($this->checks as $check) {
            $start = microtime(true);

            try {
                $result = $check->run();
            } catch (Throwable $e) {
                $result = Result::failed($check->name(), $e->getMessage());
            }

            $results[] = $result->withDuration((microtime(true) - $start) * 1000);
        }

        return HealthReport::fromResults($results);
    }
}
