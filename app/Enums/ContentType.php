<?php

namespace App\Enums;

enum ContentType: string
{
    case Article = 'article';
    case BuyingGuide = 'buying_guide';
    case HowToGuide = 'how_to_guide';
    case Faq = 'faq';
    case ComparisonArticle = 'comparison_article';
    case TroubleshootingGuide = 'troubleshooting_guide';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Article => 'Article',
            self::BuyingGuide => 'Buying guide',
            self::HowToGuide => 'How-to guide',
            self::Faq => 'FAQ',
            self::ComparisonArticle => 'Comparison article',
            self::TroubleshootingGuide => 'Troubleshooting guide',
            self::Manual => 'Manual',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }
}
