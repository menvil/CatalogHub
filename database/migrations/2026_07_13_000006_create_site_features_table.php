<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_features', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key');
            $table->boolean('is_enabled')->default(false);
            $table->json('config_json')->nullable();
            $table->timestamps();
            $table->unique(['site_id', 'feature_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_features');
    }
};
