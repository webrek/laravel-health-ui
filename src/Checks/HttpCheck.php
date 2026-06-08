<?php

namespace Webrek\HealthUi\Checks;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Webrek\HealthUi\Contracts\Check;
use Webrek\HealthUi\Result;

/**
 * Pings an external dependency over HTTP and expects a successful response.
 */
class HttpCheck implements Check
{
    public function __construct(
        private readonly string $label,
        private readonly string $url,
        private readonly int $timeout = 5,
    ) {}

    public function name(): string
    {
        return 'http:' . Str::slug($this->label);
    }

    public function run(): Result
    {
        $response = Http::timeout($this->timeout)->get($this->url);

        $meta = ['url' => $this->url, 'status' => $response->status()];

        if ($response->successful()) {
            return Result::ok($this->name(), "{$this->label} responded {$response->status()}.", $meta);
        }

        return Result::failed($this->name(), "{$this->label} responded {$response->status()}.", $meta);
    }
}
