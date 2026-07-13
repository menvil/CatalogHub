<?php

namespace App\Services\Imports;

use App\Models\Imports\NormalizationError;
use App\Models\Imports\NormalizedProductDraft;
use App\Services\Media\MediaService;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final readonly class ImportMediaDownloader
{
    public function __construct(private MediaService $mediaService) {}

    public function downloadForDraft(NormalizedProductDraft $draft): NormalizedProductDraft
    {
        $processed = [];

        foreach ($draft->media_json ?? [] as $candidate) {
            $candidate = is_string($candidate) ? ['source_url' => $candidate] : $candidate;

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
        $this->assertSafeUrl($url);

        $response = Http::timeout((int) config('imports.media_download_timeout', 10))->get($url);
        $response->throw();
        $this->assertImageResponse($response);

        $body = $response->body();
        $maximumBytes = (int) config('imports.media_download_max_bytes', 10 * 1024 * 1024);

        if (strlen($body) > $maximumBytes) {
            throw new RuntimeException('The imported media file exceeds the configured size limit.');
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'cataloghub-import-media-');

        if ($temporaryPath === false || file_put_contents($temporaryPath, $body) === false) {
            throw new RuntimeException('Unable to create a temporary imported media file.');
        }

        try {
            $filename = basename((string) parse_url($url, PHP_URL_PATH)) ?: 'imported-image.jpg';
            $mimeType = trim(explode(';', (string) $response->header('Content-Type'))[0]);
            $file = new UploadedFile($temporaryPath, $filename, $mimeType, null, true);
            $asset = $this->mediaService->uploadOriginal($file, [
                'type' => 'image',
                'source' => 'import',
            ]);

            $asset->sources()->create([
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

    private function assertSafeUrl(string $url): void
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('The imported media URL is invalid.');
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '' || $host === 'localhost') {
            throw new RuntimeException('The imported media URL is not allowed.');
        }

        if (
            filter_var($host, FILTER_VALIDATE_IP) !== false
            && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false
        ) {
            throw new RuntimeException('Private or reserved media hosts are not allowed.');
        }
    }

    private function assertImageResponse(Response $response): void
    {
        $mimeType = strtolower((string) $response->header('Content-Type'));

        if (! str_starts_with($mimeType, 'image/')) {
            throw new RuntimeException('The imported media response is not an image.');
        }
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
