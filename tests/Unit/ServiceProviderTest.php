<?php

namespace AutoCrud\Tests\Unit;

use AutoCrud\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_it_registers_config(): void
    {
        $this->assertNotNull(config('auto-crud'));
    }

    public function test_it_has_default_type_config(): void
    {
        $this->assertContains(config('auto-crud.default_type'), ['api', 'web', 'both']);
    }

    public function test_it_has_default_css_config(): void
    {
        $this->assertContains(config('auto-crud.default_css'), ['tailwind', 'bootstrap']);
    }
}
