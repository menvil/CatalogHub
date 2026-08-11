<?php

declare(strict_types=1);

namespace Tests\Fixtures\Architecture\Data;

final class InvalidRequestGlobalData
{
    public function currentLocale(): string
    {
        return request()->string('locale')->toString();
    }
}
