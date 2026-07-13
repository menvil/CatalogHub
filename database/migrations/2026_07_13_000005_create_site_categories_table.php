<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('central_category_id')->constrained('central_categories')->restrictOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->string('local_status')->nullable();
            $table->json('settings_json')->nullable();
            $table->timestamps();
            $table->unique(['site_id', 'central_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_categories');
    }
};
