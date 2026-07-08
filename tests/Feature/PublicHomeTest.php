<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicHomeTest extends TestCase
{
    public function test_home_page_renders_public_placeholder(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('CatalogHub public demo placeholder')
            ->assertSee('/build/assets/app-', false)
            ->assertSee('type="module"', false);
    }
}
