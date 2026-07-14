<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_facet_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facet_definition_id')->constrained()->cascadeOnDelete();
            $table->string('label_override')->nullable();
            $table->unsignedInteger('position_override')->nullable();
            $table->boolean('is_visible')->nullable();
            $table->boolean('default_collapsed')->nullable();
            $table->json('config_json')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'facet_definition_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_facet_overrides');
    }
};
