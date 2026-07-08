<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class StepperWizardTest extends TestCase
{
    public function test_stepper_wizard_renders_steps_and_active_current_step(): void
    {
        $steps = [
            ['key' => 'raw', 'label' => 'Raw import', 'status' => 'completed'],
            ['key' => 'mapping', 'label' => 'Mapping', 'status' => 'current'],
            ['key' => 'review', 'label' => 'Review', 'status' => 'pending'],
        ];

        $html = Blade::render(
            '<x-admin.stepper-wizard :steps="$steps" current-step="mapping" />',
            ['steps' => $steps]
        );

        $this->assertStringContainsString('data-admin-stepper="horizontal"', $html);
        $this->assertStringContainsString('Raw import', $html);
        $this->assertStringContainsString('Mapping', $html);
        $this->assertStringContainsString('Review', $html);
        $this->assertStringContainsString('data-admin-step-status="completed"', $html);
        $this->assertStringContainsString('data-admin-step-status="current"', $html);
        $this->assertStringContainsString('bg-admin-primary text-white', $html);
    }

    public function test_stepper_wizard_supports_vertical_orientation_and_error_state(): void
    {
        $steps = [
            ['key' => 'media', 'label' => 'Media download', 'status' => 'error', 'description' => 'Two files failed'],
        ];

        $html = Blade::render(
            '<x-admin.stepper-wizard :steps="$steps" orientation="vertical" />',
            ['steps' => $steps]
        );

        $this->assertStringContainsString('data-admin-stepper="vertical"', $html);
        $this->assertStringContainsString('Media download', $html);
        $this->assertStringContainsString('Two files failed', $html);
        $this->assertStringContainsString('data-admin-step-status="error"', $html);
        $this->assertStringContainsString('bg-admin-danger text-white', $html);
    }

    public function test_stepper_wizard_escapes_step_labels(): void
    {
        $steps = [
            ['key' => 'unsafe', 'label' => '<Unsafe>', 'description' => '<Description>'],
        ];

        $html = Blade::render('<x-admin.stepper-wizard :steps="$steps" />', ['steps' => $steps]);

        $this->assertStringContainsString('&lt;Unsafe&gt;', $html);
        $this->assertStringContainsString('&lt;Description&gt;', $html);
        $this->assertStringNotContainsString('<Unsafe>', $html);
    }
}
