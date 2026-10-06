<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class FrozenSchemaConsumerV2ContractTest extends TestCase
{
    public function test_historical_v2_migration_contract_is_pinned_and_has_no_live_application_dependencies(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode(file_get_contents(__DIR__.'/schema-consumer-v2-contract.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($manifest as $path => $hash) {
            $source = file_get_contents($root.'/'.$path);
            self::assertSame($hash, hash('sha256', $source), $path.': the historical v2 contract is frozen; implement later consumer versions in new files.');
            self::assertDoesNotMatchRegularExpression('/\bapp\s*\(/', $source, $path);
            preg_match_all('/^use (App\\\\[^;]+);/m', $source, $imports);
            foreach ($imports[1] as $import) {
                self::assertTrue($import === 'App\\Contracts\\Persistence\\RawSqlPersistenceBoundary' || str_starts_with($import, 'App\\Queries\\SchemaCutoverV2\\') || $import === 'App\\Services\\SchemaCutoverV2\\IdentityMutex', $path.': live application dependency '.$import);
            }
            self::assertStringNotContainsString('SiteSyncService', $source, $path);
            self::assertStringNotContainsString('App\\Models\\', $source, $path);
        }
        self::assertCount(6, $manifest);
    }
}
