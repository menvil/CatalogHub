<?php

use App\Domains\Projections\SiteSyncService;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\Site;
use App\Queries\Attributes\SchemaConsumerPreflightV2Query;
use App\Services\AttributeGlobalization\AttributeIdentityLock;
use App\Services\AttributeGlobalization\DraftAttributeIdentityV2;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    private const LOCAL = ['central_category_id', 'attribute_section_id', 'position', 'is_required', 'is_visible', 'is_searchable', 'is_sortable', 'is_filterable', 'is_comparable', 'dimension', 'canonical_unit', 'canonical_code'];

    private const EPOCH_TABLES = ['attribute_definitions', 'category_attribute_assignments', 'attribute_sections', 'attribute_options', 'attribute_mappings', 'facet_definitions', 'facet_options', 'category_comparison_attributes', 'central_product_attribute_values', 'central_products', 'central_categories', 'normalized_product_drafts', 'attribute_translations', 'attribute_option_translations', 'attribute_section_translations', 'category_translations', 'product_translations', 'unit_translations', 'content_relations'];

    public function up(): void
    {
        app(AttributeIdentityLock::class)->deployment(fn () => $this->withPortableDdl(fn () => $this->cutover()));
    }

    public function down(): void
    {
        app(AttributeIdentityLock::class)->deployment(fn () => $this->withPortableDdl(fn () => $this->restoreLegacySchema()));
    }

    private function withPortableDdl(Closure $operation): void
    {
        if (DB::getDriverName() === 'sqlite') {
            if (DB::transactionLevel() !== 0) {
                throw new RuntimeException('SQLite ownership contraction requires an isolated migration connection without an outer transaction.');
            }
            $triggers = DB::table('sqlite_master')->where('type', 'trigger')->orderBy('name')->get();
            Schema::withoutForeignKeyConstraints(function () use ($operation, $triggers): void {
                $operation();
                foreach ($triggers as $trigger) {
                    if (str_contains($trigger->name, '_v2_') || preg_match('/^(global_attribute_code_check|attribute_mapping_assignment_shape_check|facet_assignment_shape_check|attribute_section_flat_check)_/', $trigger->name)) {
                        continue;
                    }
                    if (! DB::table('sqlite_master')->where('type', 'trigger')->where('name', $trigger->name)->exists()) {
                        DB::statement($trigger->sql);
                    }
                }
                $this->assertSqliteForeignKeys();
            });
        } else {
            $operation();
        }
    }

    private function cutover(): void
    {
        $this->ddlTransaction(function (): void {
            app(AttributeIdentityLock::class)->acquire();
            app(SchemaConsumerPreflightV2Query::class)->assertReady();
            DB::table('attribute_identity_scopes')->where('id', 1)->update(['consumer_version' => 0]);
            Schema::create('schema_consumer_rollback_rows', function (Blueprint $table): void {
                $table->string('owner_table');
                $table->unsignedBigInteger('owner_id');
                $table->json('fields_json');
                $table->primary(['owner_table', 'owner_id']);
            });
            foreach (['attribute_definitions' => Schema::getColumnListing('attribute_definitions'), 'attribute_options' => Schema::getColumnListing('attribute_options'),
                'category_attribute_assignments' => ['attribute_definition_id'], 'attribute_translations' => ['attribute_definition_id'],
                'attribute_option_translations' => ['attribute_option_id'], 'facet_options' => ['value'], 'attribute_display_rules' => ['attribute_definition_id'],
                'central_product_attribute_values' => ['attribute_definition_id', 'value_enum_code', 'value_json'], 'content_relations' => ['related_id'],
                'attribute_mappings' => ['attribute_definition_id'], 'facet_definitions' => ['attribute_definition_id'], 'normalized_product_drafts' => ['attribute_identity_version', 'attributes_json']] as $table => $fields) {
                DB::table($table)->orderBy('id')->chunkById(500, function ($rows) use ($table, $fields): void {
                    DB::table('schema_consumer_rollback_rows')->insert($rows->map(fn ($row) => ['owner_table' => $table, 'owner_id' => $row->id,
                        'fields_json' => json_encode(array_intersect_key((array) $row, array_flip($fields)), JSON_THROW_ON_ERROR)])->all());
                });
            }
            $this->removeCheck('attribute_definitions', 'global_attribute_code_check');
            $this->removeCheck('attribute_mappings', 'attribute_mapping_assignment_shape_check');
            Schema::table('attribute_mappings', fn (Blueprint $table) => $table->index(['category_attribute_assignment_id', 'category_id'], 'attribute_mapping_assignment_category_idx'));
            foreach (['attribute_mappings', 'facet_definitions'] as $table) {
                $this->dropDependentKeys($table, ['attribute_definition_id']);
            }
            $factProof = $this->factProof();
            DB::transaction(function () use ($factProof): void {
                $this->repointReviewedIdentities();
                foreach (NormalizedProductDraft::query()->whereIn('status', ['pending_review', 'approved'])->where('attribute_identity_version', 1)->orderBy('id')->cursor() as $draft) {
                    DB::table('normalized_product_drafts')->where('id', $draft->id)->update([
                        'attributes_json' => json_encode(app(DraftAttributeIdentityV2::class)->candidates($draft), JSON_THROW_ON_ERROR), 'attribute_identity_version' => 2]);
                }
                if ($factProof !== $this->factProof()) {
                    throw new RuntimeException('Cutover changed Product fact snapshots or row counts; pointer transaction rolled back.');
                }
                foreach (['site_product_projections', 'site_category_projections', 'site_search_documents'] as $table) {
                    DB::table($table)->update(['status' => 'stale', 'stale_at' => now()]);
                }
                // Current outputs are rebuilt; archived catalog snapshots are separate immutable owners.
                foreach (Site::query()->orderBy('id')->cursor() as $site) {
                    $counts = app(SiteSyncService::class)->syncSite($site);
                    if ($counts['failures'] !== []) {
                        throw new RuntimeException('Target projection/search rebuild failed; identity transaction rolled back.');
                    }
                }
            });
            app(SchemaConsumerPreflightV2Query::class)->assertReady();
            foreach (['attribute_mappings', 'facet_definitions'] as $table) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('attribute_definition_id'));
            }
            $this->dropDependentKeys('attribute_definitions', self::LOCAL);
            Schema::table('attribute_definitions', function (Blueprint $table): void {
                $table->dropColumn(self::LOCAL);
                $table->unique('code');
            });
            $this->dropDependentKeys('central_product_attribute_values', ['attribute_definition_id'], false);
            Schema::table('central_product_attribute_values', fn (Blueprint $table) => $table->foreign('attribute_definition_id')->references('id')->on('attribute_definitions')->restrictOnDelete());
            $this->check('facet_definitions', 'facet_assignment_shape_check', "((source_type = 'attribute' AND category_attribute_assignment_id IS NOT NULL) OR (source_type IN ('brand', 'rating') AND category_attribute_assignment_id IS NULL))");
            $this->check('attribute_sections', 'attribute_section_flat_check', '(parent_id IS NULL)');
            Schema::table('normalized_product_drafts', fn (Blueprint $table) => $table->unsignedInteger('attribute_identity_version')->default(2)->change());
            $this->localeKeys();
            DB::table('attribute_identity_scopes')->where('id', 1)->update(['consumer_version' => 2, 'cutover_write_epoch' => DB::table('attribute_identity_scopes')->where('id', 1)->value('write_epoch')]);
            $this->epochTriggers(true);
            $this->assertSqliteForeignKeys();
        });
    }

    private function restoreLegacySchema(): void
    {
        $this->ddlTransaction(function (): void {
            app(AttributeIdentityLock::class)->acquire();
            $scope = DB::table('attribute_identity_scopes')->where('id', 1)->first();
            if ($scope->write_epoch !== $scope->cutover_write_epoch) {
                throw new RuntimeException('Target identity writes exist after cutover. Downgrade refused: restore backup and replay with a reviewed inverse transformation.');
            }
            DB::table('attribute_identity_scopes')->where('id', 1)->update(['consumer_version' => 0]);
            $this->epochTriggers(false);
            $this->removeCheck('facet_definitions', 'facet_assignment_shape_check');
            $this->removeCheck('attribute_sections', 'attribute_section_flat_check');
            Schema::table('attribute_definitions', function (Blueprint $table): void {
                $table->dropUnique(['code']);
                $table->unsignedBigInteger('central_category_id')->nullable();
                $table->unsignedBigInteger('attribute_section_id')->nullable();
                $table->unsignedInteger('position')->default(0);
                foreach (['is_required', 'is_searchable', 'is_sortable', 'is_filterable', 'is_comparable'] as $field) {
                    $table->boolean($field)->default(false);
                }
                $table->boolean('is_visible')->default(true);
                $table->string('dimension')->nullable();
                $table->string('canonical_unit')->nullable();
                $table->string('canonical_code')->nullable();
            });
            foreach (['attribute_mappings', 'facet_definitions'] as $table) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unsignedBigInteger('attribute_definition_id')->nullable());
            }
            DB::transaction(function (): void {
                foreach (['attribute_definitions', 'attribute_options'] as $table) {
                    foreach (DB::table('schema_consumer_rollback_rows')->where('owner_table', $table)->orderBy('owner_id')->cursor() as $row) {
                        $fields = json_decode($row->fields_json, true, 512, JSON_THROW_ON_ERROR);
                        if (DB::table($table)->where('id', $row->owner_id)->exists()) {
                            DB::table($table)->where('id', $row->owner_id)->update($fields);
                        } else {
                            DB::table($table)->insert($fields);
                        }
                    }
                }
                foreach (DB::table('schema_consumer_rollback_rows')->whereNotIn('owner_table', ['attribute_definitions', 'attribute_options', 'locale_key_added', 'locale_code_key_removed', 'locale_owner_key_added'])->orderBy('owner_table')->orderBy('owner_id')->cursor() as $row) {
                    DB::table($row->owner_table)->where('id', $row->owner_id)->update(json_decode($row->fields_json, true, 512, JSON_THROW_ON_ERROR));
                }
                foreach (['site_product_projections', 'site_category_projections', 'site_search_documents'] as $table) {
                    DB::table($table)->update(['status' => 'stale', 'stale_at' => now()]);
                }
            });
            Schema::table('attribute_definitions', function (Blueprint $table): void {
                $table->unique(['central_category_id', 'code']);
                $table->unique(['id', 'central_category_id'], 'attribute_definitions_id_category_unique');
                $table->unique('canonical_code');
                $table->foreign('central_category_id')->references('id')->on('central_categories')->cascadeOnDelete();
                $table->foreign(['attribute_section_id', 'central_category_id'], 'attr_def_section_cat_fk')->references(['id', 'central_category_id'])->on('attribute_sections')->restrictOnDelete();
                $table->index(['central_category_id', 'attribute_section_id', 'position'], 'attr_def_category_section_position_idx');
                $table->index(['central_category_id', 'is_filterable']);
                $table->index(['central_category_id', 'is_comparable']);
            });
            Schema::table('attribute_mappings', function (Blueprint $table): void {
                $table->foreign(['attribute_definition_id', 'category_id'], 'attribute_mappings_definition_category_fk')->references(['id', 'central_category_id'])->on('attribute_definitions')->restrictOnDelete();
                $table->foreign(['category_attribute_assignment_id', 'category_id', 'attribute_definition_id'], 'attribute_mapping_assignment_definition_fk')->references(['id', 'central_category_id', 'attribute_definition_id'])->on('category_attribute_assignments')->restrictOnDelete();
                $table->index(['category_attribute_assignment_id', 'category_id', 'attribute_definition_id'], 'attribute_mapping_assignment_membership_idx');
            });
            Schema::table('attribute_mappings', fn (Blueprint $table) => $table->dropIndex('attribute_mapping_assignment_category_idx'));
            Schema::table('facet_definitions', fn (Blueprint $table) => $table->foreign('attribute_definition_id')->references('id')->on('attribute_definitions')->nullOnDelete());
            $this->dropDependentKeys('central_product_attribute_values', ['attribute_definition_id'], false);
            Schema::table('central_product_attribute_values', fn (Blueprint $table) => $table->foreign('attribute_definition_id')->references('id')->on('attribute_definitions')->cascadeOnDelete());
            $this->check('attribute_definitions', 'global_attribute_code_check', '(central_category_id IS NOT NULL OR (canonical_code IS NOT NULL AND canonical_code = code))');
            $this->check('attribute_mappings', 'attribute_mapping_assignment_shape_check', '(category_attribute_assignment_id IS NULL OR attribute_definition_id IS NOT NULL)');
            Schema::table('normalized_product_drafts', fn (Blueprint $table) => $table->unsignedInteger('attribute_identity_version')->default(1)->change());
            foreach (DB::table('schema_consumer_rollback_rows')->where('owner_table', 'locale_code_key_removed')->get() as $row) {
                $key = json_decode($row->fields_json, true, 512, JSON_THROW_ON_ERROR);
                Schema::table($key['table'], fn (Blueprint $table) => $table->unique([$key['owner'], 'locale'], $key['name']));
            }
            foreach (DB::table('schema_consumer_rollback_rows')->where('owner_table', 'locale_key_added')->get() as $row) {
                $key = json_decode($row->fields_json, true, 512, JSON_THROW_ON_ERROR);
                Schema::table($key['table'], fn (Blueprint $table) => $table->dropUnique($key['table'].'_owner_locale_id_unique'));
                DB::table('translation_locale_identity_states')->where('owner_table', $key['table'])->update(['unique_installed' => false]);
            }
            DB::table('attribute_identity_scopes')->where('id', 1)->update(['consumer_version' => 1, 'cutover_write_epoch' => null]);
            foreach (DB::table('schema_consumer_rollback_rows')->where('owner_table', 'locale_owner_key_added')->get() as $row) {
                $key = json_decode($row->fields_json, true, 512, JSON_THROW_ON_ERROR);
                Schema::table($key['table'], fn (Blueprint $table) => $table->dropIndex($key['table'].'_owner_support_idx'));
            }
            Schema::drop('schema_consumer_rollback_rows');
            $this->assertSqliteForeignKeys();
        });
    }

    /** MariaDB DDL is not transactional; a connection mutex and persisted pause protect its boundaries. */
    private function ddlTransaction(Closure $operation): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::transaction(function (): void {
                app(AttributeIdentityLock::class)->acquire();
                app(SchemaConsumerPreflightV2Query::class)->assertReady();
            });
            $operation();
        } else {
            DB::transaction($operation);
        }
    }

    /** Frozen pointer transformation: no canonical choice is made here. */
    private function repointReviewedIdentities(): void
    {
        $targets = DB::table('attribute_definition_crosswalks')->pluck('canonical_definition_id', 'legacy_definition_id');
        $optionMaps = DB::table('attribute_option_crosswalks')->get()->keyBy('legacy_option_id');
        $options = DB::table('attribute_options')->get()->keyBy('id');
        foreach ($targets as $legacyId => $targetId) {
            if ($legacyId === $targetId || ! DB::table('attribute_definitions')->where('id', $legacyId)->exists()) {
                continue;
            }
            $codeMap = [];
            foreach ($options->where('attribute_definition_id', $legacyId) as $option) {
                $map = $optionMaps->get($option->id);
                $codeMap[$option->code] = $map->canonical_code;
                $retained = $options->get($map->canonical_option_id);
                DB::table('attribute_options')->where('id', $retained->id)->update(['attribute_definition_id' => $targetId]);
                DB::table('attribute_option_translations')->where('attribute_option_id', $option->id)->update(['attribute_option_id' => $retained->id]);
            }
            foreach (DB::table('facet_options')->whereIn('facet_definition_id', DB::table('facet_definitions')->where('source_type', 'attribute')->where('attribute_definition_id', $legacyId)->select('id'))->orderBy('id')->cursor() as $option) {
                if (isset($codeMap[$option->value])) {
                    DB::table('facet_options')->where('id', $option->id)->update(['value' => $codeMap[$option->value]]);
                }
            }
            foreach (DB::table('central_product_attribute_values')->where('attribute_definition_id', $legacyId)->orderBy('id')->cursor() as $value) {
                $changes = ['attribute_definition_id' => $targetId];
                if ($value->value_type === 'enum' && $value->value_enum_code !== null) {
                    $changes['value_enum_code'] = $codeMap[$value->value_enum_code];
                }
                if ($value->value_type === 'multi_enum' && $value->value_json !== null) {
                    $codes = json_decode($value->value_json, true, 512, JSON_THROW_ON_ERROR);
                    $changes['value_json'] = json_encode(array_map(fn ($code) => $codeMap[$code], $codes), JSON_THROW_ON_ERROR);
                }
                DB::table('central_product_attribute_values')->where('id', $value->id)->update($changes);
            }
            foreach (['category_attribute_assignments', 'attribute_translations', 'attribute_display_rules', 'attribute_mappings', 'facet_definitions'] as $table) {
                DB::table($table)->where('attribute_definition_id', $legacyId)->update(['attribute_definition_id' => $targetId]);
            }
            DB::table('content_relations')->where('related_type', 'attribute')->where('related_id', $legacyId)->update(['related_id' => $targetId]);
        }
        // References have moved before removing explicitly mapped obsolete options/definitions.
        foreach ($optionMaps as $map) {
            if ($map->legacy_option_id !== $map->canonical_option_id) {
                DB::table('attribute_options')->where('id', $map->legacy_option_id)->delete();
            }
        }
        foreach ($targets as $legacyId => $targetId) {
            if ($legacyId !== $targetId) {
                DB::table('attribute_definitions')->where('id', $legacyId)->delete();
            }
        }
    }

    /** Only explicitly mapped enum vocabulary and definition pointers may change. */
    private function factProof(): array
    {
        $hash = hash_init('sha256');
        $count = 0;
        foreach (DB::table('central_product_attribute_values')->orderBy('id')->cursor() as $value) {
            $row = (array) $value;
            unset($row['attribute_definition_id']);
            if ($row['value_type'] === 'enum') {
                unset($row['value_enum_code']);
            } elseif ($row['value_type'] === 'multi_enum') {
                unset($row['value_json']);
            }
            ksort($row);
            hash_update($hash, json_encode($row, JSON_THROW_ON_ERROR)."\n");
            $count++;
        }

        return ['count' => $count, 'snapshot_checksum' => hash_final($hash)];
    }

    private function assertSqliteForeignKeys(): void
    {
        if (DB::getDriverName() === 'sqlite' && DB::select('PRAGMA foreign_key_check') !== []) {
            throw new RuntimeException('SQLite ownership transformation failed its foreign-key proof; transaction rolled back.');
        }
    }

    /** @param list<string> $columns */
    private function dropDependentKeys(string $name, array $columns, bool $indexes = true): void
    {
        $foreignKeys = Schema::getForeignKeys($name);
        foreach ($foreignKeys as $key) {
            if (array_intersect($key['columns'], $columns) !== []) {
                Schema::table($name, fn (Blueprint $table) => $table->dropForeign(DB::getDriverName() === 'sqlite' ? $key['columns'] : $key['name']));
            }
        }
        if ($indexes) {
            foreach (Schema::getIndexes($name) as $index) {
                if (! $index['primary'] && array_intersect($index['columns'], $columns) !== []) {
                    Schema::table($name, fn (Blueprint $table) => $index['unique'] ? $table->dropUnique($index['name']) : $table->dropIndex($index['name']));
                }
            }
        }
    }

    private function removeCheck(string $table, string $name): void
    {
        if (DB::getDriverName() === 'sqlite') {
            foreach (['insert', 'update'] as $operation) {
                DB::statement("DROP TRIGGER IF EXISTS {$name}_{$operation}");
            }
        } else {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$name}");
        }
    }

    private function check(string $table, string $name, string $expression): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK {$expression}");

            return;
        }
        $expression = preg_replace('/\b(source_type|category_attribute_assignment_id|parent_id|central_category_id|canonical_code|code|attribute_definition_id)\b/', 'NEW.$1', $expression);
        foreach (['insert', 'update'] as $operation) {
            DB::statement("CREATE TRIGGER {$name}_{$operation} BEFORE {$operation} ON {$table} WHEN NOT {$expression} BEGIN SELECT RAISE(ABORT, '{$name}'); END");
        }
    }

    private function epochTriggers(bool $install): void
    {
        if ($install && DB::getDriverName() === 'pgsql') {
            DB::unprepared('CREATE OR REPLACE FUNCTION schema_consumer_record_write() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN UPDATE attribute_identity_scopes SET write_epoch = write_epoch + 1 WHERE id = 1; RETURN NULL; END $$');
        }
        foreach (self::EPOCH_TABLES as $table) {
            foreach (['insert', 'update', 'delete'] as $operation) {
                $name = $table.'_v2_'.$operation;
                // CategoryLock obtains a portable write lock with an unchanged revision.
                // That lock alone must not close the inverse boundary.
                $fields = ['name', 'slug', 'parent_id', 'position', 'status', 'schema_revision', 'schema_status', 'schema_reviewed_revision', 'schema_approved_revision', 'schema_reviewed_by_user_id', 'schema_approved_by_user_id', 'schema_reviewed_at', 'schema_approved_at'];
                $categoryUpdate = $table === 'central_categories' && $operation === 'update';
                $condition = $categoryUpdate ? implode(' OR ', array_map(fn ($field) => match (DB::getDriverName()) {
                    'pgsql' => "OLD.{$field} IS DISTINCT FROM NEW.{$field}",
                    'sqlite' => "OLD.{$field} IS NOT NEW.{$field}",
                    default => "NOT (OLD.{$field} <=> NEW.{$field})",
                }, $fields)) : '';
                if (! $install) {
                    DB::statement('DROP TRIGGER IF EXISTS '.$name.(DB::getDriverName() === 'pgsql' ? ' ON '.$table : ''));
                } elseif (DB::getDriverName() === 'pgsql') {
                    $when = $categoryUpdate ? " WHEN ({$condition})" : '';
                    DB::statement("CREATE TRIGGER {$name} AFTER {$operation} ON {$table} FOR EACH ROW{$when} EXECUTE FUNCTION schema_consumer_record_write()");
                } elseif (DB::getDriverName() === 'sqlite') {
                    $when = $categoryUpdate ? " WHEN ({$condition})" : '';
                    DB::statement("CREATE TRIGGER {$name} AFTER {$operation} ON {$table}{$when} BEGIN UPDATE attribute_identity_scopes SET write_epoch = write_epoch + 1 WHERE id = 1; END");
                } else {
                    $body = $categoryUpdate ? "BEGIN IF ({$condition}) THEN UPDATE attribute_identity_scopes SET write_epoch = write_epoch + 1 WHERE id = 1; END IF; END" : 'UPDATE attribute_identity_scopes SET write_epoch = write_epoch + 1 WHERE id = 1';
                    DB::statement("CREATE TRIGGER {$name} AFTER {$operation} ON {$table} FOR EACH ROW {$body}");
                }
            }
        }
        if (! $install && DB::getDriverName() === 'pgsql') {
            DB::statement('DROP FUNCTION schema_consumer_record_write()');
        }
    }

    private function localeKeys(): void
    {
        foreach (['category_translations' => 'category_id', 'attribute_translations' => 'attribute_definition_id', 'attribute_section_translations' => 'attribute_section_id', 'unit_translations' => 'measurement_unit_id', 'product_translations' => 'product_id'] as $table => $owner) {
            $indexes = collect(Schema::getIndexes($table));
            if (! $indexes->contains(fn ($index) => $index['columns'] === [$owner])) {
                DB::table('schema_consumer_rollback_rows')->insert(['owner_table' => 'locale_owner_key_added', 'owner_id' => array_search($table, ['category_translations', 'attribute_translations', 'attribute_section_translations', 'unit_translations', 'product_translations']), 'fields_json' => json_encode(['table' => $table], JSON_THROW_ON_ERROR)]);
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($owner, $table.'_owner_support_idx'));
            }
            if (! $indexes->contains(fn ($index) => $index['unique'] && $index['columns'] === [$owner, 'locale_id'])) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unique([$owner, 'locale_id'], $table.'_owner_locale_id_unique'));
                DB::table('schema_consumer_rollback_rows')->insert(['owner_table' => 'locale_key_added', 'owner_id' => array_search($table, ['category_translations', 'attribute_translations', 'attribute_section_translations', 'unit_translations', 'product_translations']), 'fields_json' => json_encode(['table' => $table], JSON_THROW_ON_ERROR)]);
            }
            DB::table('translation_locale_identity_states')->where('owner_table', $table)->update(['unique_installed' => true]);
            foreach ($indexes as $index) {
                if ($index['unique'] && $index['columns'] === [$owner, 'locale']) {
                    DB::table('schema_consumer_rollback_rows')->insert(['owner_table' => 'locale_code_key_removed', 'owner_id' => array_search($table, ['category_translations', 'attribute_translations', 'attribute_section_translations', 'unit_translations', 'product_translations']), 'fields_json' => json_encode(['table' => $table, 'owner' => $owner, 'name' => $index['name']], JSON_THROW_ON_ERROR)]);
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropUnique($index['name']));
                }
            }
        }
    }
};
