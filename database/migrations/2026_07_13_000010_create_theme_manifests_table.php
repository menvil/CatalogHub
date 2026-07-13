<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_manifests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('theme_id')->unique()->constrained('themes')->cascadeOnDelete();
            $table->json('manifest_json');
            $table->json('supports_json')->nullable();
            $table->json('layouts_json')->nullable();
            $table->string('schema_version')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->json('validation_errors_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_manifests');
    }
};
