<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('central_brands', function (Blueprint $table): void {
            $table->integer('founded_year')->nullable();
            $table->string('support_url')->nullable();
            $table->string('contact_email', 254)->nullable();
            $table->string('primary_color', 7)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('central_brands', function (Blueprint $table): void {
            $table->dropColumn(['founded_year', 'support_url', 'contact_email', 'primary_color']);
        });
    }
};
