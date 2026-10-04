<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_hierarchy_scopes', function (Blueprint $table): void {
            $table->string('scope_key', 64)->primary();
            $table->unsignedBigInteger('revision')->default(0);
        });

        DB::table('category_hierarchy_scopes')->insert(['scope_key' => 'root', 'revision' => 0]);
        DB::table('central_categories')->orderBy('id')->chunkById(500, function ($categories): void {
            foreach ($categories as $category) {
                DB::table('category_hierarchy_scopes')->insert([
                    'scope_key' => 'parent:'.$category->id,
                    'revision' => 0,
                ]);
            }
        });

        Schema::table('central_categories', function (Blueprint $table): void {
            $table->unsignedBigInteger('schema_revision')->default(1);
            $table->unsignedBigInteger('schema_reviewed_revision')->nullable();
            $table->unsignedBigInteger('schema_approved_revision')->nullable();
            $table->foreignId('schema_reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('schema_reviewed_at')->nullable();
            $table->foreignId('schema_approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('schema_approved_at')->nullable();
        });

        DB::table('central_categories')->whereIn('schema_status', ['reviewed', 'approved'])
            ->update(['schema_reviewed_revision' => 1]);
        DB::table('central_categories')->where('schema_status', 'approved')
            ->update(['schema_approved_revision' => 1]);
    }

    public function down(): void
    {
        Schema::table('central_categories', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('schema_reviewed_by_user_id');
            $table->dropConstrainedForeignId('schema_approved_by_user_id');
            $table->dropColumn([
                'schema_revision', 'schema_reviewed_revision', 'schema_approved_revision',
                'schema_reviewed_at', 'schema_approved_at',
            ]);
        });
        Schema::dropIfExists('category_hierarchy_scopes');
    }
};
