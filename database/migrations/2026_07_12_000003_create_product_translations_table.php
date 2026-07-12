<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('central_products')->cascadeOnDelete();
            $table->foreignId('locale_id')->constrained('locales')->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('name')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('status')->default('missing');
            $table->string('source_hash')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'locale']);
            $table->index(['locale', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_translations');
    }
};
