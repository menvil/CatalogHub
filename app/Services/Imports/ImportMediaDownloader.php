<?php

namespace App\Services\Imports;

use App\Models\Imports\NormalizationError;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\MediaSource;
use App\Services\Media\MediaService;
use Closure;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final readonly class ImportMediaDownloader
{
    /** @param (Closure(string): list<string>)|null $hostResolver */
    public function __construct(
        private MediaService $mediaService,
        private ?Closure $hostResolver = null,
    ) {}

    public function downloadForDraft(NormalizedProductDraft $draft): NormalizedProductDraft
    {
        $processed = [];

        foreach ($draft->media_json ?? [] as $candidate) {
            $candidate = $this->normalizeCandidate($candidate);

            try {
                $processed[] = $this->downloadCandidate($draft, $candidate);
            } catch (Throwable $exception) {
                $candidate['status'] = 'failed';
                $candidate['error'] = $exception->getMessage();
                $processed[] = $candidate;
                $this->recordError($draft, $candidate, $exception);
            }
        }

        $draft->forceFill(['media_json' => $processed])->save();

        return $draft->refresh();
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @return array<string, mixed>
     */
    private function downloadCandidate(NormalizedProductDraft $draft, array $candidate): array
    {
        $url = (string) ($candidate['source_url'] ?? $candidate['url'] ?? '');
        $curlResolve = $this->assertSafeUrl($url);
        $maximumBytes = (int) config('imports.media_download_max_bytes', 10 * 1024 * 1024);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'cataloghub-import-media-');

        if ($temporaryPath === false) {
            throw new RuntimeException('Unable to create a temporary imported media file.');
        }

        try {
            $response = $this->downloadToFile($url, $temporaryPath, $maximumBytes, $curlResolve);
            $response->throw();
            $this->assertImageResponse($response, $temporaryPath);

            $filename = basename((string) parse_url($url, PHP_URL_PATH)) ?: 'imported-image.jpg';
            $mimeType = trim(explode(';', (string) $response->header('Content-Type'))[0]);
            $file = new UploadedFile($temporaryPath, $filename, $mimeType, null, true);
            $asset = $this->mediaService->uploadOriginal($file, [
                'type' => 'image',
                'source' => 'import',
            ]);

            MediaSource::query()->firstOrCreate([
                'media_asset_id' => $asset->id,
            ], [
                'source_type' => 'import',
                'source_url' => $url,
                'source_name' => parse_url($url, PHP_URL_HOST),
                'license_type' => $candidate['license_type'] ?? null,
                'license_url' => $candidate['license_url'] ?? null,
                'attribution' => $candidate['attribution'] ?? null,
                'metadata' => [
                    'import_batch_id' => $draft->import_batch_id,
                    'normalized_product_draft_id' => $draft->id,
                ],
            ]);
        } finally {
            @unlink($temporaryPath);
        }

        $candidate['source_url'] = $url;
        $candidate['media_asset_id'] = $asset->id;
        $candidate['status'] = 'downloaded';
        unset($candidate['error']);

        return $candidate;
    }

    /** @return array<string, mixed> */
    private function normalizeCandidate(mixed $candidate): array
    {
        if (is_array($candidate)) {
            return $candidate;
        }

        if (is_string($candidate)) {
            return ['source_url' => $candidate];
        }

        return [
            'source_url' => '',
            'raw_value' => is_scalar($candidate) || $candidate === null
                ? $candidate
                : get_debug_type($candidate),
        ];
    }

    private function assertSafeUrl(string $url): ?string
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('The imported media URL is invalid.');
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $parsedHost = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = trim($parsedHost, '[]');

        if (! in_array($scheme, ['http', 'https'], true) || $host === '' || $host === 'localhost') {
            throw new RuntimeException('The imported media URL is not allowed.');
        }

        if (
            filter_var($host, FILTER_VALIDATE_IP) !== false
        ) {
            $this->assertPublicIp($host);

            return null;
        }

        $addresses = $this->resolveHostAddresses($host);

        if ($addresses === []) {
            throw new RuntimeException('The imported media host could not be resolved.');
        }

        foreach ($addresses as $address) {
            $this->assertPublicIp($address);
        }

        if (! defined('CURLOPT_RESOLVE')) {
            throw new RuntimeException('Secure media hostname resolution is not available.');
        }

        $port = (int) (parse_url($url, PHP_URL_PORT) ?: ($scheme === 'https' ? 443 : 80));
        $pinnedAddresses = array_map(
            static fn (string $address): string => str_contains($address, ':') ? "[{$address}]" : $address,
            $addresses,
        );

        return sprintf('%s:%d:%s', $host, $port, implode(',', $pinnedAddresses));
    }

    private function assertImageResponse(Response $response, string $path): void
    {
        $mimeType = strtolower((string) $response->header('Content-Type'));
        $imageInfo = @getimagesize($path);

        if (
            ! str_starts_with($mimeType, 'image/')
            || $imageInfo === false
            || ! str_starts_with(strtolower($imageInfo['mime']), 'image/')
        ) {
            throw new RuntimeException('The imported media response is not an image.');
        }
    }

    private function downloadToFile(
        string $url,
        string $path,
        int $maximumBytes,
        ?string $curlResolve,
    ): Response {
        $options = [
            'allow_redirects' => false,
            'proxy' => '',
            'sink' => $path,
            'progress' => static function (int $downloadTotal, int $downloadedBytes) use ($maximumBytes): void {
                if ($downloadTotal > $maximumBytes || $downloadedBytes > $maximumBytes) {
                    throw new RuntimeException('The imported media file exceeds the configured size limit.');
                }
            },
        ];

        if ($curlResolve !== null) {
            $options['curl'] = [CURLOPT_RESOLVE => [$curlResolve]];
        }

        $response = Http::timeout((int) config('imports.media_download_timeout', 10))
            ->withOptions($options)
            ->get($url);

        if ((filesize($path) ?: 0) === 0) {
            $this->writeResponseStream($response, $path, $maximumBytes);
        }

        if ((filesize($path) ?: 0) > $maximumBytes) {
            throw new RuntimeException('The imported media file exceeds the configured size limit.');
        }

        return $response;
    }

    private function writeResponseStream(Response $response, string $path, int $maximumBytes): void
    {
        $source = $response->toPsrResponse()->getBody();
        $destination = fopen($path, 'wb');

        if ($destination === false) {
            throw new RuntimeException('Unable to create a temporary imported media file.');
        }

        $written = 0;

        try {
            while (! $source->eof()) {
                $chunk = $source->read(min(8192, $maximumBytes - $written + 1));
                $written += strlen($chunk);

                if ($written > $maximumBytes) {
                    throw new RuntimeException('The imported media file exceeds the configured size limit.');
                }

                if ($chunk !== '' && fwrite($destination, $chunk) === false) {
                    throw new RuntimeException('Unable to create a temporary imported media file.');
                }
            }
        } finally {
            fclose($destination);
        }
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
            throw new RuntimeException('Private or reserved media hosts are not allowed.');
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

    /** @param array<string, mixed> $candidate */
    private function recordError(
        NormalizedProductDraft $draft,
        array $candidate,
        Throwable $exception,
    ): void {
        NormalizationError::query()->create([
            'import_batch_id' => $draft->import_batch_id,
            'raw_product_id' => $draft->raw_product_id,
            'normalized_product_draft_id' => $draft->id,
            'severity' => 'warning',
            'code' => 'media_download_failed',
            'message' => $exception->getMessage(),
            'raw_key' => 'media',
            'raw_value' => (string) ($candidate['source_url'] ?? $candidate['url'] ?? ''),
            'context_json' => ['candidate' => $candidate],
        ]);
    }
}
