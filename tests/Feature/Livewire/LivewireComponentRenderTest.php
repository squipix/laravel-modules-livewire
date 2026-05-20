<?php

namespace Mhmiton\LaravelModulesLivewire\Tests\Feature\Livewire;

use Livewire\Livewire;
use Mhmiton\LaravelModulesLivewire\Providers\LivewireComponentServiceProvider;
use Mhmiton\LaravelModulesLivewire\Tests\TestCase;

class LivewireComponentRenderTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function test_module_make_livewire_command_works()
    {
        $this->artisan('module:make-livewire', [
            'component' => 'Pages/AboutPage',
            'module' => 'Core',
            '--inline' => true,
        ])->assertExitCode(0);

        $componentClass = 'Modules\Core\Livewire\Pages\AboutPage';
        $filePath = base_path('Modules/Core/app/Livewire/Pages/AboutPage.php');

        // Verify the file was created
        $this->assertFileExists($filePath);

        // Verify the component class can be loaded and exists
        require_once $filePath;
        $this->assertTrue(class_exists($componentClass), 'Livewire component class was not created');

        // Verify the class is a valid Livewire component
        $component = new $componentClass();
        $this->assertInstanceOf(\Livewire\Component::class, $component);

        // Verify the namespace and class name are correct
        $reflection = new \ReflectionClass($componentClass);
        $this->assertEquals('AboutPage', $reflection->getShortName());
        $this->assertEquals('Modules\Core\Livewire\Pages', $reflection->getNamespaceName());
    }
}
