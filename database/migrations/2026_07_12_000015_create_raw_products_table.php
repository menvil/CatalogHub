<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_batch_id')->index()->constrained('import_batches')->cascadeOnDelete();
            $table->foreignId('import_source_id')->index()->constrained('import_sources')->restrictOnDelete();
            $table->string('external_id')->nullable()->index();
            $table->unsignedInteger('source_row_number')->nullable();
            $table->string('raw_title')->nullable();
            $table->string('raw_brand')->nullable();
            $table->string('raw_category')->nullable();
            $table->json('raw_payload_json');
            $table->string('payload_hash', 64)->index();
            $table->string('status')->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_products');
    }
};
