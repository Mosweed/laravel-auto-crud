<?php

namespace AutoCrud\Tests\Feature;

use AutoCrud\Tests\TestCase;

class PublishLayoutCommandTest extends TestCase
{
    public function test_command_is_registered(): void
    {
        $this->assertTrue(
            collect($this->app['Illuminate\Contracts\Console\Kernel']->all())
                ->has('crud:layout')
        );
    }

    public function test_command_has_css_option(): void
    {
        $command = $this->app['Illuminate\Contracts\Console\Kernel']->all()['crud:layout'];
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('css'));
    }

    public function test_command_has_force_option(): void
    {
        $command = $this->app['Illuminate\Contracts\Console\Kernel']->all()['crud:layout'];
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('force'));
    }
}
