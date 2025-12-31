<?php

namespace AutoCrud\Generators;

use Illuminate\Support\Str;

class LayoutGenerator extends BaseGenerator
{
    protected array $navItems = [];

    public function __construct(string $modelName = '', array $options = [], array $navItems = [])
    {
        parent::__construct($modelName, $options);
        $this->navItems = $navItems;
    }

    public function generate(): array
    {
        $css = $this->getCssFramework();
        $path = resource_path('views/components/app-layout.blade.php');

        // Check if layout already exists
        if ($this->files->exists($path) && !($this->options['force'] ?? false)) {
            return [
                'path' => $path,
                'created' => false,
            ];
        }

        $content = $this->getStubContent("layouts/{$css}/app.blade.stub");
        $content = $this->replaceNavItems($content);

        $this->createFile($path, $content);

        return [
            'path' => $path,
            'created' => true,
        ];
    }

    /**
     * Replace navigation item placeholders.
     */
    protected function replaceNavItems(string $content): string
    {
        $navItems = $this->generateNavItems();
        $navItemsMobile = $this->generateNavItemsMobile();

        $content = str_replace('{{ navItems }}', $navItems, $content);
        $content = str_replace('{{ navItemsMobile }}', $navItemsMobile, $content);

        return $content;
    }

    /**
     * Generate desktop navigation items.
     */
    protected function generateNavItems(): string
    {
        if (empty($this->navItems)) {
            return $this->getDefaultNavItem();
        }

        $css = $this->getCssFramework();
        $items = [];

        foreach ($this->navItems as $item) {
            $route = Str::kebab(Str::plural($item));
            $label = Str::plural($item);

            if ($css === 'tailwind') {
                $items[] = "<a href=\"{{ route('{$route}.index') }}\" class=\"rounded-md px-3 py-2 text-sm font-medium text-white hover:bg-primary-500 hover:bg-opacity-75\">{$label}</a>";
            } else {
                $items[] = "<li class=\"nav-item\"><a class=\"nav-link\" href=\"{{ route('{$route}.index') }}\">{$label}</a></li>";
            }
        }

        return implode("\n                                ", $items);
    }

    /**
     * Generate mobile navigation items.
     */
    protected function generateNavItemsMobile(): string
    {
        if (empty($this->navItems)) {
            return $this->getDefaultNavItemMobile();
        }

        $items = [];

        foreach ($this->navItems as $item) {
            $route = Str::kebab(Str::plural($item));
            $label = Str::plural($item);

            $items[] = "<a href=\"{{ route('{$route}.index') }}\" class=\"block rounded-md px-3 py-2 text-base font-medium text-white hover:bg-primary-500 hover:bg-opacity-75\">{$label}</a>";
        }

        return implode("\n                    ", $items);
    }

    /**
     * Get default navigation item placeholder.
     */
    protected function getDefaultNavItem(): string
    {
        $css = $this->getCssFramework();

        if ($css === 'tailwind') {
            return '{{-- Voeg hier je navigatie items toe --}}
                                {{-- <a href="{{ route(\'products.index\') }}" class="rounded-md px-3 py-2 text-sm font-medium text-white hover:bg-primary-500 hover:bg-opacity-75">Products</a> --}}';
        }

        return '{{-- Voeg hier je navigatie items toe --}}
                    {{-- <li class="nav-item"><a class="nav-link" href="{{ route(\'products.index\') }}">Products</a></li> --}}';
    }

    /**
     * Get default mobile navigation item placeholder.
     */
    protected function getDefaultNavItemMobile(): string
    {
        return '{{-- Voeg hier je navigatie items toe --}}
                    {{-- <a href="{{ route(\'products.index\') }}" class="block rounded-md px-3 py-2 text-base font-medium text-white hover:bg-primary-500 hover:bg-opacity-75">Products</a> --}}';
    }

    /**
     * Add a navigation item to an existing layout.
     */
    public function addNavItem(string $modelName): bool
    {
        $path = resource_path('views/components/app-layout.blade.php');

        if (!$this->files->exists($path)) {
            return false;
        }

        $content = $this->files->get($path);
        $route = Str::kebab(Str::plural($modelName));
        $label = Str::plural($modelName);

        // Check if nav item already exists
        if (str_contains($content, "route('{$route}.index')")) {
            return false;
        }

        $css = $this->getCssFramework();

        // Find the nav items section and add new item
        if ($css === 'tailwind') {
            $newItem = "<a href=\"{{ route('{$route}.index') }}\" class=\"rounded-md px-3 py-2 text-sm font-medium text-white hover:bg-primary-500 hover:bg-opacity-75\">{$label}</a>";
            $mobileItem = "<a href=\"{{ route('{$route}.index') }}\" class=\"block rounded-md px-3 py-2 text-base font-medium text-white hover:bg-primary-500 hover:bg-opacity-75\">{$label}</a>";

            // Add to desktop nav
            if (preg_match('/(<a href="[^"]*dashboard[^"]*"[^>]*>Dashboard<\/a>)/', $content, $matches)) {
                $content = str_replace($matches[1], $matches[1] . "\n                                " . $newItem, $content);
            }

            // Add to mobile nav
            if (preg_match('/(<a href="[^"]*dashboard[^"]*"[^>]*class="block[^>]*>[\s]*Dashboard[\s]*<\/a>)/', $content, $matches)) {
                $content = str_replace($matches[1], $matches[1] . "\n                    " . $mobileItem, $content);
            }
        } else {
            $newItem = "<li class=\"nav-item\"><a class=\"nav-link\" href=\"{{ route('{$route}.index') }}\">{$label}</a></li>";

            if (preg_match('/(<a class="nav-link" href="[^"]*dashboard[^"]*">Dashboard<\/a><\/li>)/', $content, $matches)) {
                $content = str_replace($matches[1], $matches[1] . "\n                    " . $newItem, $content);
            }
        }

        $this->files->put($path, $content);

        return true;
    }
}
