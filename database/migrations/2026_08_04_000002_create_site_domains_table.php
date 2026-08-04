<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_domains', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('host')->unique();
            $table->string('type')->index();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['site_id', 'is_active']);
            $table->index(['site_id', 'is_primary']);
        });

        /** @var array<string, int> $normalizedHosts */
        $normalizedHosts = [];
        $sites = DB::table('sites')
            ->whereNotNull('domain')
            ->where('domain', '!=', '')
            ->select(['id', 'domain', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->get();
        $backfill = [];

        foreach ($sites as $site) {
            $host = $this->normalizeHost((string) $site->domain);

            if (isset($normalizedHosts[$host])) {
                throw new RuntimeException(
                    "Duplicate normalized site domain [{$host}] for sites [{$normalizedHosts[$host]}] and [{$site->id}].",
                );
            }

            $normalizedHosts[$host] = $site->id;
            $backfill[] = [
                'site_id' => $site->id,
                'host' => $host,
                'type' => 'primary',
                'is_primary' => true,
                'is_active' => true,
                'created_at' => $site->created_at,
                'updated_at' => $site->updated_at,
            ];
        }

        if ($backfill !== []) {
            DB::table('site_domains')->insert($backfill);
        }
    }

    private function normalizeHost(string $input): string
    {
        $input = trim($input);
        $parts = parse_url(str_contains($input, '://') ? $input : '//'.$input);
        $host = is_array($parts) ? ($parts['host'] ?? null) : null;
        $normalized = is_string($host) ? strtolower(rtrim($host, '.')) : '';

        if ($normalized === ''
            || ! str_contains($normalized, '.')
            || filter_var($normalized, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            throw new RuntimeException("Cannot normalize existing site domain [{$input}].");
        }

        return $normalized;
    }

    public function down(): void
    {
        Schema::dropIfExists('site_domains');
    }
};
