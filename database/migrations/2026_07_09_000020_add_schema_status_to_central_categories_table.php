<?php

use App\Enums\CategorySchemaStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('central_categories', function (Blueprint $table): void {
            $table->string('schema_status')
                ->default(CategorySchemaStatus::default()->value)
                ->after('status')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('central_categories', function (Blueprint $table): void {
            $table->dropIndex(['schema_status']);
            $table->dropColumn('schema_status');
        });
    }
};
