<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('central_product_id')->nullable()->constrained('central_products')->nullOnDelete();
            $table->foreignId('central_category_id')->nullable()->constrained('central_categories')->nullOnDelete();
            $table->string('type');
            $table->string('status')->default('new');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->text('message')->nullable();
            $table->string('locale', 20)->nullable();
            $table->string('source')->nullable();
            $table->timestamp('consent_accepted_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'status']);
            $table->index(['site_id', 'type']);
            $table->index(['site_id', 'central_product_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
