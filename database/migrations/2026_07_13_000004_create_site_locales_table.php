<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_locales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('locale_code');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['site_id', 'locale_code']);
            $table->foreign('locale_code')->references('code')->on('locales')->restrictOnDelete();
        });

        DB::statement('CREATE UNIQUE INDEX site_locales_single_default_per_site ON site_locales (site_id) WHERE is_default = true');

        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER site_locales_default_enabled_insert
                BEFORE INSERT ON site_locales
                WHEN NEW.is_default = 1 AND NEW.is_enabled = 0
                BEGIN
                    SELECT RAISE(ABORT, 'site_locales default locale must be enabled');
                END
                SQL);
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER site_locales_default_enabled_update
                BEFORE UPDATE OF is_default, is_enabled ON site_locales
                WHEN NEW.is_default = 1 AND NEW.is_enabled = 0
                BEGIN
                    SELECT RAISE(ABORT, 'site_locales default locale must be enabled');
                END
                SQL);
        } else {
            DB::statement(<<<'SQL'
                ALTER TABLE site_locales
                ADD CONSTRAINT site_locales_default_must_be_enabled
                CHECK (NOT is_default OR is_enabled)
                SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_locales');
    }
};
