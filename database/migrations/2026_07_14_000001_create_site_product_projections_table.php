<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_product_projections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 20);
            $table->foreignId('central_product_id')->constrained('central_products')->cascadeOnDelete();
            $table->unsignedBigInteger('central_product_version')->nullable();
            $table->string('slug');
            $table->string('canonical_url')->nullable();
            $table->string('title')->nullable();
            $table->string('status')->default('pending');
            $table->json('payload_json');
            $table->json('seo_json')->nullable();
            $table->json('media_json')->nullable();
            $table->json('search_summary_json')->nullable();
            $table->string('checksum')->nullable()->index();
            $table->timestamp('built_at')->nullable();
            $table->timestamp('stale_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->unique(
                ['site_id', 'locale', 'central_product_id'],
                'site_product_projections_identity_unique',
            );
            $table->index(['site_id', 'locale', 'slug']);
            $table->index(['site_id', 'status']);
            $table->index('central_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_product_projections');
    }
};
