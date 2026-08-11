<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_heartbeats', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->timestamp('last_ran_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_heartbeats');
    }
};
