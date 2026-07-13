<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('field');
            $table->string('locale_code')->default('');
            $table->json('value_json');
            $table->text('reason')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamps();
            $table->unique(['site_id', 'entity_type', 'entity_id', 'field', 'locale_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_overrides');
    }
};
