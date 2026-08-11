<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Enums\ActionProgressStatus;
use DateTimeInterface;
use InvalidArgumentException;

final readonly class ActionProgressViewModel
{
    public function __construct(
        public ActionProgressStatus $status,
        public string $message,
        public ?DateTimeInterface $startedAt = null,
        public ?DateTimeInterface $completedAt = null,
    ) {
        if (trim($message) === '') {
            throw new InvalidArgumentException('Action progress messages cannot be empty.');
        }

        if ($status === ActionProgressStatus::Idle && ($startedAt !== null || $completedAt !== null)) {
            throw new InvalidArgumentException('Idle actions cannot have timestamps.');
        }

        if ($status === ActionProgressStatus::Pending && ($startedAt === null || $completedAt !== null)) {
            throw new InvalidArgumentException('Pending actions require only a start timestamp.');
        }

        if (in_array($status, [ActionProgressStatus::Success, ActionProgressStatus::Failure], true)) {
            if ($startedAt === null || $completedAt === null) {
                throw new InvalidArgumentException('Completed actions require start and completion timestamps.');
            }

            if ($completedAt < $startedAt) {
                throw new InvalidArgumentException('Action completion cannot precede its start.');
            }
        }
    }

    public static function idle(string $message): self
    {
        return new self(ActionProgressStatus::Idle, $message);
    }

    public static function pending(string $message, DateTimeInterface $startedAt): self
    {
        return new self(ActionProgressStatus::Pending, $message, $startedAt);
    }

    public static function success(string $message, DateTimeInterface $startedAt, DateTimeInterface $completedAt): self
    {
        return new self(ActionProgressStatus::Success, $message, $startedAt, $completedAt);
    }

    public static function failure(string $message, DateTimeInterface $startedAt, DateTimeInterface $completedAt): self
    {
        return new self(ActionProgressStatus::Failure, $message, $startedAt, $completedAt);
    }
}
