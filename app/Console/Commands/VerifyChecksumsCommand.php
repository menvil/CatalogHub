<?php

namespace App\Console\Commands;

use App\Models\SyncLog;
use App\Services\Backup\ChecksumVerifier;
use Illuminate\Console\Command;

final class VerifyChecksumsCommand extends Command
{
    protected $signature = 'cataloghub:verify-checksums
        {--snapshot= : Verify only the completed snapshot with this UUID}
        {--media : Also verify original media files represented by manifests}';

    protected $description = 'Verify stored SHA-256 checksums for snapshot and optional media files';

    public function handle(ChecksumVerifier $verifier): int
    {
        $startedAt = now();
        $snapshotUuid = $this->option('snapshot');
        $snapshotUuid = is_string($snapshotUuid) && $snapshotUuid !== '' ? $snapshotUuid : null;
        $includeMedia = (bool) $this->option('media');
        $result = $verifier->verify($snapshotUuid, $includeMedia);

        foreach ($result->issues as $issue) {
            $this->error($issue);
        }

        SyncLog::query()->create([
            'operation' => 'verify_snapshot_checksums',
            'status' => $result->hasIssues() ? 'failed' : 'completed',
            'triggered_by' => 'system',
            'started_at' => $startedAt,
            'finished_at' => now(),
            'affected_count' => $result->checkedCount,
            'error_message' => $result->issues[0] ?? null,
            'context_json' => [
                'snapshot_uuid' => $snapshotUuid,
                'include_media' => $includeMedia,
                'issue_count' => count($result->issues),
            ],
        ]);

        if ($result->hasIssues()) {
            $this->error('Checksum verification failed with '.count($result->issues).' issue(s).');

            return self::FAILURE;
        }

        $this->info("Verified {$result->checkedCount} file checksum(s).");

        return self::SUCCESS;
    }
}
