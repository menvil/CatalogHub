<?php

namespace App\Actions\Sites;

use App\Models\Site;
use Illuminate\Support\Facades\DB;

final class UpsertSiteSeoOverridesAction
{
    public function __construct(
        private readonly UpsertSiteOverrideAction $overrides,
    ) {}

    public function handle(
        Site $site,
        string $entityType,
        int $entityId,
        string $localeCode,
        ?string $metaTitle,
        ?string $metaDescription,
        ?string $introText,
    ): void {
        DB::transaction(function () use (
            $site,
            $entityType,
            $entityId,
            $localeCode,
            $metaTitle,
            $metaDescription,
            $introText,
        ): void {
            $this->overrides->handle($site, $entityType, $entityId, 'meta_title', $localeCode, $metaTitle);
            $this->overrides->handle($site, $entityType, $entityId, 'meta_description', $localeCode, $metaDescription);
            $this->overrides->handle($site, $entityType, $entityId, 'intro_text', $localeCode, $introText);
        });
    }
}
