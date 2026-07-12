<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type')->default('image')->index();
            $table->string('source')->nullable()->index();
            $table->string('disk');
            $table->string('original_path');
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('checksum')->nullable()->unique();
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->index(['disk', 'original_path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
