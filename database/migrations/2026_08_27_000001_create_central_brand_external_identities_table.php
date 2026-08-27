<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('central_brand_external_identities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('central_brand_id')->index()->constrained('central_brands')->cascadeOnDelete();
            $table->foreignId('import_source_id')->index()->constrained('import_sources')->restrictOnDelete();
            $table->string('external_id', 255);
            $table->string('external_id_hash', 64);
            $table->text('external_url')->nullable();
            $table->timestamps();

            $table->unique(['import_source_id', 'external_id_hash'], 'brand_external_identity_source_hash_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_brand_external_identities');
    }
};
