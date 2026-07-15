<?php

namespace App\Services\Pricing;

use Closure;
use InvalidArgumentException;

final readonly class OutboundPriceSourceUrlGuard
{
    /** @param (Closure(string): list<string>)|null $hostResolver */
    public function __construct(
        private ?Closure $hostResolver = null,
    ) {}

    /**
     * @param  array<array-key, mixed>  $allowedHosts
     * @return array<string, mixed>
     */
    public function requestOptions(string $url, array $allowedHosts = []): array
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('Price source URL is invalid.');
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower(trim((string) parse_url($url, PHP_URL_HOST), '[]'));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '' || $host === 'localhost') {
            throw new InvalidArgumentException('Price source URL must use an allowed HTTP(S) host.');
        }

        $allowedHosts = $this->normalizeAllowedHosts($allowedHosts);

        if ($allowedHosts !== [] && ! in_array($host, $allowedHosts, true)) {
            throw new InvalidArgumentException("Price source host [{$host}] is not allowlisted.");
        }

        $options = [
            'allow_redirects' => false,
            'proxy' => '',
        ];

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            $this->assertPublicIp($host);

            return $options;
        }

        $addresses = $this->resolveHostAddresses($host);

        if ($addresses === []) {
            throw new InvalidArgumentException("Price source host [{$host}] could not be resolved.");
        }

        foreach ($addresses as $address) {
            $this->assertPublicIp($address);
        }

        if (! defined('CURLOPT_RESOLVE')) {
            throw new InvalidArgumentException('Secure price source hostname resolution is unavailable.');
        }

        $port = (int) (parse_url($url, PHP_URL_PORT) ?: ($scheme === 'https' ? 443 : 80));
        $pinnedAddresses = array_map(
            static fn (string $address): string => str_contains($address, ':') ? "[{$address}]" : $address,
            $addresses,
        );
        $options['curl'] = [
            CURLOPT_RESOLVE => [sprintf('%s:%d:%s', $host, $port, implode(',', $pinnedAddresses))],
        ];

        return $options;
    }

    /**
     * @param  array<array-key, mixed>  $hosts
     * @return list<string>
     */
    private function normalizeAllowedHosts(array $hosts): array
    {
        $normalized = [];

        foreach ($hosts as $host) {
            if (! is_string($host) || trim($host) === '') {
                throw new InvalidArgumentException('Price source allowed_hosts must contain host names.');
            }

            $normalized[] = strtolower(trim($host, "[] \t\n\r\0\x0B"));
        }

        return array_values(array_unique($normalized));
    }

    private function assertPublicIp(string $address): void
    {
        if (
            filter_var($address, FILTER_VALIDATE_IP) === false
            || filter_var(
                $address,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            ) === false
        ) {
            throw new InvalidArgumentException('Private or reserved price source hosts are not allowed.');
        }
    }

    /** @return list<string> */
    private function resolveHostAddresses(string $host): array
    {
        if ($this->hostResolver instanceof Closure) {
            return ($this->hostResolver)($host);
        }

        $records = dns_get_record($host, DNS_A | DNS_AAAA);

        if ($records === false) {
            return [];
        }

        $addresses = [];

        foreach ($records as $record) {
            $address = $record['ip'] ?? $record['ipv6'] ?? null;

            if (is_string($address)) {
                $addresses[] = $address;
            }
        }

        return array_values(array_unique($addresses));
    }
}
