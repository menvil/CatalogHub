<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locales', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('language_code')->index();
            $table->string('region_code')->nullable()->index();
            $table->string('name');
            $table->string('native_name')->nullable();
            $table->string('direction')->default('ltr');
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->unsignedInteger('position')->default(0)->index();
            $table->timestamps();
        });

        DB::statement('CREATE UNIQUE INDEX locales_single_default_unique ON locales (is_default) WHERE is_default = true');
    }

    public function down(): void
    {
        Schema::dropIfExists('locales');
    }
};
