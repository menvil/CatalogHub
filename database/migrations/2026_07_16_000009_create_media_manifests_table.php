<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_manifests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('catalog_snapshot_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('media_asset_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('asset_uuid')->nullable();
            $table->string('original_path')->nullable();
            $table->json('variants_json')->nullable();
            $table->string('checksum')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('status')->default('pending');
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['catalog_snapshot_id', 'status']);
            $table->index('media_asset_id');
            $table->index('asset_uuid');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_manifests');
    }
};
