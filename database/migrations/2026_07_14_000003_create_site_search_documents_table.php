<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_search_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 20);
            $table->string('document_type');
            $table->unsignedBigInteger('document_id');
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->string('status')->default('pending');
            $table->longText('search_text')->nullable();
            $table->json('filter_values_json')->nullable();
            $table->json('sort_values_json')->nullable();
            $table->json('payload_json')->nullable();
            $table->string('checksum')->nullable()->index();
            $table->timestamp('built_at')->nullable();
            $table->timestamp('stale_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['site_id', 'locale', 'document_type', 'document_id'],
                'site_search_documents_identity_unique',
            );
            $table->index(['site_id', 'locale', 'status']);
            $table->index(['site_id', 'locale', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_search_documents');
    }
};
