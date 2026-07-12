<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('measurement_unit_id')->constrained('measurement_units')->cascadeOnDelete();
            $table->foreignId('locale_id')->constrained('locales')->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('short_name')->nullable();
            $table->string('long_name')->nullable();
            $table->string('plural_name')->nullable();
            $table->string('symbol_position')->default('after');
            $table->boolean('space_between_value_and_unit')->default(true);
            $table->string('status')->default('missing');
            $table->string('source_hash')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['measurement_unit_id', 'locale']);
            $table->index(['locale', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_translations');
    }
};
