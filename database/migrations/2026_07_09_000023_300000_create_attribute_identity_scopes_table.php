<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Permanent transaction mutex for membership discovery and normal schema/spec/import writes.
        Schema::create('attribute_identity_scopes', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
        });
        DB::table('attribute_identity_scopes')->insert(['id' => 1]);
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_identity_scopes');
    }
};
