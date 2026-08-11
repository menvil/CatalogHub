<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use App\Enums\ActionProgressStatus;
use App\ViewModels\ActionProgressViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Blade;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ActionProgressStateTest extends TestCase
{
    #[DataProvider('stateProvider')]
    public function test_typed_action_states_render_without_fabricated_percentage(ActionProgressViewModel $progress, string $marker): void
    {
        $html = Blade::render(
            '<x-ui.states.action-progress :progress="$progress" action-label="Start export" retry-label="Retry export" reset-label="Dismiss" />',
            ['progress' => $progress],
        );

        $this->assertStringContainsString('data-ui-action-progress="'.$marker.'"', $html);
        $this->assertStringContainsString($progress->message, $html);
        $this->assertStringNotContainsString('%', $html);
    }

    public function test_pending_blocks_duplicate_start_and_terminal_states_expose_explicit_next_actions(): void
    {
        $pending = Blade::render(
            '<x-ui.states.action-progress :progress="$progress" action-label="Start export" />',
            ['progress' => ActionProgressViewModel::pending('Export is running.', CarbonImmutable::parse('2026-08-09 10:00:00 UTC'))],
        );
        $failure = Blade::render(
            '<x-ui.states.action-progress :progress="$progress" retry-label="Retry export" />',
            ['progress' => ActionProgressViewModel::failure('Export failed safely.', CarbonImmutable::parse('2026-08-09 10:00:00 UTC'), CarbonImmutable::parse('2026-08-09 10:01:00 UTC'))],
        );
        $success = Blade::render(
            '<x-ui.states.action-progress :progress="$progress" reset-label="Dismiss result" />',
            ['progress' => ActionProgressViewModel::success('Export completed.', CarbonImmutable::parse('2026-08-09 10:00:00 UTC'), CarbonImmutable::parse('2026-08-09 10:01:00 UTC'))],
        );

        $this->assertStringContainsString('aria-busy="true"', $pending);
        $this->assertStringContainsString('disabled', $pending);
        $this->assertStringContainsString('data-action-progress-retry', $failure);
        $this->assertStringContainsString('Retry export', $failure);
        $this->assertStringContainsString('data-action-progress-reset', $success);
        $this->assertStringContainsString('Dismiss result', $success);
    }

    public function test_invalid_action_progress_timestamps_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ActionProgressViewModel(
            ActionProgressStatus::Success,
            'Completed.',
            CarbonImmutable::parse('2026-08-09 10:01:00 UTC'),
            CarbonImmutable::parse('2026-08-09 10:00:00 UTC'),
        );
    }

    public function test_completion_cannot_precede_start_within_the_same_second(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ActionProgressViewModel(
            ActionProgressStatus::Success,
            'Completed.',
            CarbonImmutable::parse('2026-08-09 10:00:00.500000 UTC'),
            CarbonImmutable::parse('2026-08-09 10:00:00.499999 UTC'),
        );
    }

    #[DataProvider('invalidStateProvider')]
    public function test_every_action_progress_state_contract_rejects_invalid_values(
        ActionProgressStatus $status,
        string $message,
        ?CarbonImmutable $startedAt,
        ?CarbonImmutable $completedAt,
    ): void {
        $this->expectException(InvalidArgumentException::class);

        new ActionProgressViewModel($status, $message, $startedAt, $completedAt);
    }

    /** @return iterable<string, array{ActionProgressStatus, string, ?CarbonImmutable, ?CarbonImmutable}> */
    public static function invalidStateProvider(): iterable
    {
        $started = CarbonImmutable::parse('2026-08-09 10:00:00 UTC');
        $completed = CarbonImmutable::parse('2026-08-09 10:01:00 UTC');

        yield 'empty message' => [ActionProgressStatus::Idle, ' ', null, null];
        yield 'idle start timestamp' => [ActionProgressStatus::Idle, 'Idle.', $started, null];
        yield 'idle completion timestamp' => [ActionProgressStatus::Idle, 'Idle.', null, $completed];
        yield 'pending without start' => [ActionProgressStatus::Pending, 'Pending.', null, null];
        yield 'pending with completion' => [ActionProgressStatus::Pending, 'Pending.', $started, $completed];
        yield 'success without start' => [ActionProgressStatus::Success, 'Completed.', null, $completed];
        yield 'failure without completion' => [ActionProgressStatus::Failure, 'Failed.', $started, null];
    }

    /** @return iterable<string, array{ActionProgressViewModel, string}> */
    public static function stateProvider(): iterable
    {
        $started = CarbonImmutable::parse('2026-08-09 10:00:00 UTC');
        $completed = CarbonImmutable::parse('2026-08-09 10:01:00 UTC');

        yield 'idle' => [ActionProgressViewModel::idle('Ready to export.'), 'idle'];
        yield 'pending' => [ActionProgressViewModel::pending('Export is running.', $started), 'pending'];
        yield 'success' => [ActionProgressViewModel::success('Export completed.', $started, $completed), 'success'];
        yield 'failure' => [ActionProgressViewModel::failure('Export failed safely.', $started, $completed), 'failure'];
    }
}
