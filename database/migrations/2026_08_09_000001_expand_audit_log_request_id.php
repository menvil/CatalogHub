<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const UPDATE_TRIGGER = 'audit_log_entries_prevent_update';

    private const DELETE_TRIGGER = 'audit_log_entries_prevent_delete';

    private const POSTGRES_FUNCTION = 'cataloghub_prevent_audit_log_entry_mutation';

    public function up(): void
    {
        $this->dropImmutabilityTriggers();

        Schema::table('audit_log_entries', function (Blueprint $table): void {
            $table->string('request_id', 128)->nullable()->change();
        });

        $this->createImmutabilityTriggers();
    }

    public function down(): void
    {
        // The original migration now creates the 128-character contract. Keeping
        // this width also prevents truncating already-recorded request IDs.
    }

    private function createImmutabilityTriggers(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION cataloghub_prevent_audit_log_entry_mutation()
                RETURNS trigger
                LANGUAGE plpgsql
                AS $$
                BEGIN
                    RAISE EXCEPTION 'Audit log entries are append-only.';
                END;
                $$
                SQL);
        }

        foreach ([self::UPDATE_TRIGGER => 'UPDATE', self::DELETE_TRIGGER => 'DELETE'] as $trigger => $operation) {
            match ($driver) {
                'sqlite' => DB::unprepared(<<<SQL
                    CREATE TRIGGER {$trigger}
                    BEFORE {$operation} ON audit_log_entries
                    BEGIN
                        SELECT RAISE(ABORT, 'Audit log entries are append-only.');
                    END
                    SQL),
                'mysql', 'mariadb' => DB::unprepared(<<<SQL
                    CREATE TRIGGER {$trigger}
                    BEFORE {$operation} ON audit_log_entries
                    FOR EACH ROW
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Audit log entries are append-only.'
                    SQL),
                'pgsql' => DB::unprepared(<<<SQL
                    CREATE TRIGGER {$trigger}
                    BEFORE {$operation} ON audit_log_entries
                    FOR EACH ROW
                    EXECUTE FUNCTION cataloghub_prevent_audit_log_entry_mutation()
                    SQL),
                default => throw new RuntimeException('Audit log immutability is unsupported for this database driver.'),
            };
        }
    }

    private function dropImmutabilityTriggers(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS '.self::UPDATE_TRIGGER.' ON audit_log_entries');
            DB::unprepared('DROP TRIGGER IF EXISTS '.self::DELETE_TRIGGER.' ON audit_log_entries');
            DB::unprepared('DROP FUNCTION IF EXISTS '.self::POSTGRES_FUNCTION.'()');

            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS '.self::UPDATE_TRIGGER);
        DB::unprepared('DROP TRIGGER IF EXISTS '.self::DELETE_TRIGGER);
    }
};
