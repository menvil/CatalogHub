<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const UPDATE_TRIGGER = 'audit_log_entries_prevent_update';

    private const DELETE_TRIGGER = 'audit_log_entries_prevent_delete';

    private const POSTGRES_FUNCTION = 'cataloghub_prevent_audit_log_entry_mutation';

    public function up(): void
    {
        try {
            $this->createTriggers();
        } catch (Throwable $exception) {
            $this->dropTriggers();

            throw $exception;
        }
    }

    public function down(): void
    {
        $this->dropTriggers();
    }

    private function createTriggers(): void
    {
        switch (DB::getDriverName()) {
            case 'sqlite':
                $this->createSqliteTriggers();
                break;
            case 'mysql':
            case 'mariadb':
                $this->createMysqlTriggers();
                break;
            case 'pgsql':
                $this->createPostgresTriggers();
                break;
            default:
                throw new RuntimeException('Audit log immutability is unsupported for this database driver.');
        }
    }

    private function dropTriggers(): void
    {
        switch (DB::getDriverName()) {
            case 'sqlite':
            case 'mysql':
            case 'mariadb':
                $this->dropSqliteOrMysqlTriggers();
                break;
            case 'pgsql':
                $this->dropPostgresTriggers();
                break;
        }
    }

    private function createSqliteTriggers(): void
    {
        foreach ([self::UPDATE_TRIGGER => 'UPDATE', self::DELETE_TRIGGER => 'DELETE'] as $trigger => $operation) {
            DB::unprepared(<<<SQL
                CREATE TRIGGER {$trigger}
                BEFORE {$operation} ON audit_log_entries
                BEGIN
                    SELECT RAISE(ABORT, 'Audit log entries are append-only.');
                END
                SQL);
        }
    }

    private function createMysqlTriggers(): void
    {
        foreach ([self::UPDATE_TRIGGER => 'UPDATE', self::DELETE_TRIGGER => 'DELETE'] as $trigger => $operation) {
            DB::unprepared(<<<SQL
                CREATE TRIGGER {$trigger}
                BEFORE {$operation} ON audit_log_entries
                FOR EACH ROW
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Audit log entries are append-only.'
                SQL);
        }
    }

    private function createPostgresTriggers(): void
    {
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

        foreach ([self::UPDATE_TRIGGER => 'UPDATE', self::DELETE_TRIGGER => 'DELETE'] as $trigger => $operation) {
            DB::unprepared(<<<SQL
                CREATE TRIGGER {$trigger}
                BEFORE {$operation} ON audit_log_entries
                FOR EACH ROW
                EXECUTE FUNCTION cataloghub_prevent_audit_log_entry_mutation()
                SQL);
        }
    }

    private function dropSqliteOrMysqlTriggers(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS '.self::UPDATE_TRIGGER);
        DB::unprepared('DROP TRIGGER IF EXISTS '.self::DELETE_TRIGGER);
    }

    private function dropPostgresTriggers(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS '.self::UPDATE_TRIGGER.' ON audit_log_entries');
        DB::unprepared('DROP TRIGGER IF EXISTS '.self::DELETE_TRIGGER.' ON audit_log_entries');
        DB::unprepared('DROP FUNCTION IF EXISTS '.self::POSTGRES_FUNCTION.'()');
    }
};
