<?php

use App\Services\ReferenceData\CountrySynchronizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table): void {
            $table->id();
            $table->string('alpha2', 2)->unique();
            $table->string('alpha3', 3)->unique();
            $table->string('numeric_code', 3)->unique();
            $table->string('canonical_name');
            $table->string('region_code', 3)->nullable();
            $table->string('subregion_code', 3)->nullable();
            $table->string('intermediate_region_code', 3)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('country_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->restrictOnDelete();
            $table->string('locale');
            $table->string('name');
            $table->timestamps();
            $table->unique(['country_id', 'locale']);
            $table->index(['locale', 'name']);
        });

        app(CountrySynchronizer::class)->sync(
            database_path('reference-data/countries'),
            manifestFilename: 'manifest-v1.json',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('country_translations');
        Schema::dropIfExists('countries');
    }
};
