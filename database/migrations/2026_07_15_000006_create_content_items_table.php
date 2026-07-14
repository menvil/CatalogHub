<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['site_id', 'type']);
            $table->index(['site_id', 'status']);
            $table->index(['site_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_items');
    }
};
