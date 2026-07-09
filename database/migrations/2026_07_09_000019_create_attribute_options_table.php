<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attribute_definition_id')->constrained('attribute_definitions')->cascadeOnDelete();
            $table->string('code');
            $table->string('label');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['attribute_definition_id', 'code']);
            $table->index(['attribute_definition_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_options');
    }
};
