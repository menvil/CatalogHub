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

        $driver = DB::getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('CREATE UNIQUE INDEX locales_single_default_unique ON locales (is_default) WHERE is_default = true');
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(<<<'SQL'
                ALTER TABLE locales
                ADD COLUMN default_locale_key TINYINT
                    GENERATED ALWAYS AS (CASE WHEN is_default = 1 THEN 1 ELSE NULL END) STORED,
                ADD UNIQUE INDEX locales_single_default_unique (default_locale_key)
                SQL);
        } else {
            throw new RuntimeException("Unsupported database driver for locale constraints: {$driver}");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('locales');
    }
};
