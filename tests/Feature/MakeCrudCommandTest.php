<?php

namespace AutoCrud\Tests\Feature;

use AutoCrud\Tests\TestCase;

class MakeCrudCommandTest extends TestCase
{
    public function test_command_is_registered(): void
    {
        $this->assertTrue(
            collect($this->app['Illuminate\Contracts\Console\Kernel']->all())
                ->has('make:crud')
        );
    }

    public function test_command_requires_name_argument(): void
    {
        $this->artisan('make:crud')
            ->assertFailed();
    }

    public function test_command_has_type_option(): void
    {
        $command = $this->app['Illuminate\Contracts\Console\Kernel']->all()['make:crud'];
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('type'));
    }

    public function test_command_has_fields_option(): void
    {
        $command = $this->app['Illuminate\Contracts\Console\Kernel']->all()['make:crud'];
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('fields'));
    }

    public function test_command_has_css_option(): void
    {
        $command = $this->app['Illuminate\Contracts\Console\Kernel']->all()['make:crud'];
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('css'));
    }

    public function test_command_has_all_option(): void
    {
        $command = $this->app['Illuminate\Contracts\Console\Kernel']->all()['make:crud'];
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('all'));
    }

    public function test_command_has_soft_deletes_option(): void
    {
        $command = $this->app['Illuminate\Contracts\Console\Kernel']->all()['make:crud'];
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('soft-deletes'));
    }

    public function test_command_has_livewire_option(): void
    {
        $command = $this->app['Illuminate\Contracts\Console\Kernel']->all()['make:crud'];
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('livewire'));
    }
}
