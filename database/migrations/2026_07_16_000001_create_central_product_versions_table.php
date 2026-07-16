<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('central_product_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('central_product_id')->constrained('central_products')->cascadeOnDelete();
            $table->unsignedBigInteger('version');
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_type');
            $table->text('reason')->nullable();
            $table->json('snapshot_json')->nullable();
            $table->json('diff_json')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['central_product_id', 'version']);
            $table->index(['central_product_id', 'created_at']);
            $table->index('change_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_product_versions');
    }
};
