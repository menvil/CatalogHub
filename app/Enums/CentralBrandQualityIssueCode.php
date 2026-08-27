<?php

declare(strict_types=1);

namespace App\Enums;

enum CentralBrandQualityIssueCode: string
{
    case CountryMissing = 'brand_country_missing';
    case WebsiteMissing = 'brand_website_missing';
    case FoundedYearMissing = 'brand_founded_year_missing';
    case SupportContactMissing = 'brand_support_contact_missing';
    case PrimaryColorMissing = 'brand_primary_color_missing';
    case LogoMissing = 'brand_logo_missing';
    case LogoUnusable = 'brand_logo_unusable';
    case TranslationMissing = 'brand_translation_missing';
    case TranslationOutdated = 'brand_translation_outdated';
}
