<?php

declare(strict_types=1);

namespace App\Enums;

enum ActionProgressStatus: string
{
    case Idle = 'idle';
    case Pending = 'pending';
    case Success = 'success';
    case Failure = 'failure';
}
