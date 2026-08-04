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

        DB::table('sites')
            ->whereNotNull('domain')
            ->where('domain', '!=', '')
            ->select(['id', 'domain', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->each(function (object $site): void {
                $input = trim((string) $site->domain);
                $parts = parse_url(str_contains($input, '://') ? $input : '//'.$input);
                $host = is_array($parts) ? ($parts['host'] ?? null) : null;

                if (! is_string($host) || $host === '') {
                    throw new RuntimeException("Cannot normalize existing site domain [{$input}].");
                }

                DB::table('site_domains')->insert([
                    'site_id' => $site->id,
                    'host' => strtolower(rtrim($host, '.')),
                    'type' => 'primary',
                    'is_primary' => true,
                    'is_active' => true,
                    'created_at' => $site->created_at,
                    'updated_at' => $site->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_domains');
    }
};
