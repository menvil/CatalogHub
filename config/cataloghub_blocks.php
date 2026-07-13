<?php

return [
    'hero_search' => [
        'name' => 'Hero Search',
        'description' => 'Homepage hero with a catalog search entry point.',
        'category' => 'hero',
        'supported_page_types_json' => ['home'],
        'required_features_json' => [],
        'config_schema_json' => [
            'title' => 'string',
            'subtitle' => 'string',
            'search_placeholder' => 'string',
            'show_category_shortcuts' => 'boolean',
        ],
        'view_component' => 'blocks.hero-search',
    ],
    'popular_categories' => [
        'name' => 'Popular Categories',
        'description' => 'Shortcuts to categories enabled for the site.',
        'category' => 'navigation',
        'supported_page_types_json' => ['home'],
        'required_features_json' => [],
        'config_schema_json' => [
            'title' => 'string',
            'limit' => 'integer',
            'layout' => 'grid|list|chips',
        ],
        'view_component' => 'blocks.popular-categories',
    ],
];
