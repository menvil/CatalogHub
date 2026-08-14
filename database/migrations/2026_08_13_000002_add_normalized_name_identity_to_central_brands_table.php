<?php

use App\Support\Normalization\BrandInputNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertExistingNamesAreUnique();

        Schema::table('central_brands', function (Blueprint $table): void {
            $table->text('normalized_name')->nullable();
            $table->char('normalized_name_hash', 64)->nullable();
        });

        $this->backfillNameIdentities();

        Schema::table('central_brands', function (Blueprint $table): void {
            $table->text('normalized_name')->nullable(false)->change();
            $table->char('normalized_name_hash', 64)->nullable(false)->change();
            $table->unique('normalized_name_hash');
        });
    }

    public function down(): void
    {
        Schema::table('central_brands', function (Blueprint $table): void {
            $table->dropUnique(['normalized_name_hash']);
            $table->dropColumn(['normalized_name', 'normalized_name_hash']);
        });
    }

    private function assertExistingNamesAreUnique(): void
    {
        /** @var array<string, array{identity: string, id: int|string}> $seen */
        $seen = [];

        DB::table('central_brands')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->chunkById(500, function ($brands) use (&$seen): void {
                foreach ($brands as $brand) {
                    $name = BrandInputNormalizer::name((string) $brand->name);
                    $identity = BrandInputNormalizer::nameIdentity($name);
                    $hash = BrandInputNormalizer::nameIdentityHash($name);

                    if (isset($seen[$hash])) {
                        $kind = hash_equals($seen[$hash]['identity'], $identity)
                            ? 'duplicate Unicode-normalized canonical names'
                            : 'a SHA-256 identity collision';

                        throw new RuntimeException(sprintf(
                            'Cannot add central brand name identity: brands %s and %s have %s.',
                            (string) $seen[$hash]['id'],
                            (string) $brand->id,
                            $kind,
                        ));
                    }

                    $seen[$hash] = ['identity' => $identity, 'id' => $brand->id];
                }
            });
    }

    private function backfillNameIdentities(): void
    {
        DB::table('central_brands')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->chunkById(500, function ($brands): void {
                foreach ($brands as $brand) {
                    $name = BrandInputNormalizer::name((string) $brand->name);

                    DB::table('central_brands')
                        ->where('id', $brand->id)
                        ->update([
                            'name' => $name,
                            'normalized_name' => BrandInputNormalizer::nameIdentity($name),
                            'normalized_name_hash' => BrandInputNormalizer::nameIdentityHash($name),
                        ]);
                }
            });
    }
};
