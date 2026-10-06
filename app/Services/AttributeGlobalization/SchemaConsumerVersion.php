<?php

namespace App\Services\AttributeGlobalization;

/** Identity-bearing payload contract; ID-only job envelopes have no such payload. */
final class SchemaConsumerVersion
{
    public const int CURRENT = 2;

    public const array PUBLISHABLE_STATUSES = ['pending_review', 'approved'];
}
