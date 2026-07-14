<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facet_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('facet_definition_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->string('label_override')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('config_json')->nullable();
            $table->timestamps();

            $table->unique(['facet_definition_id', 'value']);
            $table->index(['facet_definition_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facet_options');
    }
};
