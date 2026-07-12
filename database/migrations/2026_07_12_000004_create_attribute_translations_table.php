<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attribute_definition_id')->constrained('attribute_definitions')->cascadeOnDelete();
            $table->foreignId('locale_id')->constrained('locales')->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('label')->nullable();
            $table->string('short_label')->nullable();
            $table->text('help_text')->nullable();
            $table->string('status')->default('missing');
            $table->string('source_hash')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['attribute_definition_id', 'locale']);
            $table->index(['locale', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_translations');
    }
};
