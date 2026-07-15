<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_source_credentials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('price_source_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('encrypted_credentials_json');
            $table->string('status')->default('missing')->index();
            $table->timestamp('last_rotated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_source_credentials');
    }
};
