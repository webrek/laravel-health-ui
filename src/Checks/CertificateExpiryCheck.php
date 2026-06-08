<?php

namespace Webrek\HealthUi\Checks;

use Closure;
use Illuminate\Support\Str;
use RuntimeException;
use Webrek\HealthUi\Contracts\Check;
use Webrek\HealthUi\Result;

/**
 * Warns before a host's TLS certificate expires.
 */
class CertificateExpiryCheck implements Check
{
    /**
     * @param  (Closure(): int)|null  $expiryResolver  Returns the certificate's expiry as a Unix timestamp. Overridable for testing.
     */
    public function __construct(
        private readonly string $host,
        private readonly int $warningDays = 14,
        private readonly int $failureDays = 3,
        private readonly int $timeout = 5,
        private readonly ?Closure $expiryResolver = null,
    ) {}

    public function name(): string
    {
        return 'certificate:' . Str::slug($this->host);
    }

    public function run(): Result
    {
        $expiresAt = ($this->expiryResolver ?? fn (): int => $this->fetchExpiry())();
        $days = (int) floor(($expiresAt - time()) / 86400);

        $meta = ['host' => $this->host, 'days_until_expiry' => $days];
        $message = "{$this->host} certificate expires in {$days} day(s).";

        if ($days <= $this->failureDays) {
            return Result::failed($this->name(), $message, $meta);
        }

        if ($days <= $this->warningDays) {
            return Result::warning($this->name(), $message, $meta);
        }

        return Result::ok($this->name(), $message, $meta);
    }

    /**
     * @infection-ignore-all Live TLS network I/O that cannot be exercised in tests.
     */
    private function fetchExpiry(): int
    {
        $context = stream_context_create([
            'ssl' => ['capture_peer_cert' => true, 'verify_peer' => false, 'verify_peer_name' => false],
        ]);

        $client = @stream_socket_client(
            "ssl://{$this->host}:443",
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context,
        );

        if ($client === false) {
            throw new RuntimeException("Unable to connect to {$this->host}: {$errstr}");
        }

        $params = stream_context_get_params($client);
        fclose($client);

        $certificate = $params['options']['ssl']['peer_certificate'] ?? null;

        if ($certificate === null) {
            throw new RuntimeException("No certificate presented by {$this->host}.");
        }

        $parsed = openssl_x509_parse($certificate);

        return (int) ($parsed['validTo_time_t'] ?? 0);
    }
}
