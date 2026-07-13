<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_home_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('block_code');
            $table->unsignedInteger('position');
            $table->boolean('enabled')->default(true);
            $table->json('config_json')->nullable();
            $table->json('visibility_json')->nullable();
            $table->timestamps();

            $table->foreign('block_code')->references('code')->on('block_registry')->restrictOnDelete();
            $table->unique(['site_id', 'block_code', 'position'], 'site_home_blocks_site_block_position_unique');
            $table->index(['site_id', 'position'], 'site_home_blocks_site_position_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_home_blocks');
    }
};
