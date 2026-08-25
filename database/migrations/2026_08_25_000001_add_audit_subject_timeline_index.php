<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_log_entries', function (Blueprint $table): void {
            $table->index(['subject_type', 'subject_id', 'created_at'], 'audit_subject_timeline_index');
        });
    }

    public function down(): void
    {
        Schema::table('audit_log_entries', function (Blueprint $table): void {
            $table->dropIndex('audit_subject_timeline_index');
        });
    }
};
