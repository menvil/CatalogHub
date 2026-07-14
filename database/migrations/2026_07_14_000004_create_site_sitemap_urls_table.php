<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_sitemap_urls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 20);
            $table->string('url');
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('changefreq')->nullable();
            $table->decimal('priority', 2, 1)->nullable();
            $table->timestamp('lastmod_at')->nullable();
            $table->string('status')->default('active');
            $table->string('checksum')->nullable();
            $table->timestamps();

            $table->unique(
                ['site_id', 'locale', 'url'],
                'site_sitemap_urls_identity_unique',
            );
            $table->index(['site_id', 'locale', 'status']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_sitemap_urls');
    }
};
