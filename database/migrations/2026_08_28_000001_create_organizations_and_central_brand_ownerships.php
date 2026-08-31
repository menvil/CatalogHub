<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            // Case-folding may expand a valid 255-character display name.
            $table->string('normalized_name', 512)->index();
            $table->timestamps();
        });

        Schema::create('central_brand_ownerships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('central_brand_id')->unique()->constrained('central_brands')->restrictOnDelete();
            $table->foreignId('organization_id')->index()->constrained('organizations')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_brand_ownerships');
        Schema::dropIfExists('organizations');
    }
};
