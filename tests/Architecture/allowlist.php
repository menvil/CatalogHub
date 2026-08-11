<?php

declare(strict_types=1);

/**
 * Exact-file exceptions only. Every future entry must include owner, reason and
 * removal task; namespace and directory exemptions are intentionally invalid.
 *
 * @return array<string, list<array{file: string, owner: string, reason: string, task: string}>>
 */
return [
    'cross_context_import' => [],
    'request_in_dto' => [],
    'admin_in_domain' => [],
    'raw_permission' => [],
];
