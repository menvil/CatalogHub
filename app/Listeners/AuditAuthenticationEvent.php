<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\AuditAction;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Throwable;

final readonly class AuditAuthenticationEvent
{
    public function __construct(private AuditRecorder $audit) {}

    public function handle(Login|Logout $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        try {
            $this->audit->record(
                $event instanceof Login ? AuditAction::Login : AuditAction::Logout,
                $this->audit->requestContext(),
                $event->user,
                $event->user,
                $this->audit->requestSite(),
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
