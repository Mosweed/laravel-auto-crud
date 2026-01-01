<?php

namespace AutoCrud\Generators;

use Illuminate\Support\Str;

class LivewireGenerator extends BaseGenerator
{
    protected array $columns;

    public function __construct(string $modelName, array $options = [], array $columns = [])
    {
        parent::__construct($modelName, $options);
        $this->columns = $columns;
    }

    public function generate(): array
    {
        $results = [];

        $results['table'] = $this->generateTableComponent();
        $results['form'] = $this->generateFormComponent();
        $results['table_view'] = $this->generateTableView();
        $results['form_view'] = $this->generateFormView();

        return $results;
    }

    protected function generateTableComponent(): array
    {
        $content = $this->getStubContent('livewire/table.stub');
        $content = $this->replacePlaceholders($content, $this->getTableReplacements());

        $directory = config('auto-crud.paths.livewire') . '/' . $this->getModelPlural();
        $path = $directory . '/' . $this->modelName . 'Table.php';
        $created = $this->createFile($path, $content);

        return [
            'path' => $path,
            'created' => $created,
        ];
    }

    protected function generateFormComponent(): array
    {
        $content = $this->getStubContent('livewire/form.stub');
        $content = $this->replacePlaceholders($content, $this->getFormReplacements());

        $directory = config('auto-crud.paths.livewire') . '/' . $this->getModelPlural();
        $path = $directory . '/' . $this->modelName . 'Form.php';
        $created = $this->createFile($path, $content);

        return [
            'path' => $path,
            'created' => $created,
        ];
    }

    protected function generateTableView(): array
    {
        $css = $this->getCssFramework();
        $content = $this->getStubContent("livewire/views/{$css}/table.blade.stub");
        $content = $this->replacePlaceholders($content, $this->getTableViewReplacements());

        $directory = config('auto-crud.paths.livewire_views') . '/' . $this->getViewPath();
        $path = $directory . '/' . $this->getModelKebab() . '-table.blade.php';
        $created = $this->createFile($path, $content);

        return [
            'path' => $path,
            'created' => $created,
        ];
    }

    protected function generateFormView(): array
    {
        $css = $this->getCssFramework();
        $content = $this->getStubContent("livewire/views/{$css}/form.blade.stub");
        $content = $this->replacePlaceholders($content, $this->getFormViewReplacements());

        $directory = config('auto-crud.paths.livewire_views') . '/' . $this->getViewPath();
        $path = $directory . '/' . $this->getModelKebab() . '-form.blade.php';
        $created = $this->createFile($path, $content);

        return [
            'path' => $path,
            'created' => $created,
        ];
    }

    protected function getTableReplacements(): array
    {
        $replacements = $this->getCommonReplacements();
        $replacements['namespace'] = config('auto-crud.namespaces.livewire');
        $replacements['modelNamespace'] = config('auto-crud.namespaces.models');
        $replacements['defaultSortField'] = 'created_at';

        if ($this->hasSoftDeletes()) {
            $replacements['softDeleteProperties'] = "    public array \$selectedIds = [];";
            $replacements['trashedQueryString'] = "        'showTrashed' => ['except' => false],";
            $replacements['trashedScope'] = "            ->when(\$this->showTrashed, fn (\$q) => \$q->withTrashed())";
            $replacements['softDeleteMethods'] = $this->generateSoftDeleteMethods();
        } else {
            $replacements['softDeleteProperties'] = '';
            $replacements['trashedQueryString'] = '';
            $replacements['trashedScope'] = '';
            $replacements['softDeleteMethods'] = '';
        }

        return $replacements;
    }

    protected function getFormReplacements(): array
    {
        $replacements = $this->getCommonReplacements();
        $replacements['namespace'] = config('auto-crud.namespaces.livewire');
        $replacements['modelNamespace'] = config('auto-crud.namespaces.models');
        $replacements['properties'] = $this->generateProperties();
        $replacements['rules'] = $this->generateRules();
        $replacements['fillableFields'] = $this->generateFillableFields();

        return $replacements;
    }

    protected function getTableViewReplacements(): array
    {
        $replacements = $this->getCommonReplacements();
        $replacements['tableHeaders'] = $this->generateTableHeaders();
        $replacements['tableColumns'] = $this->generateTableColumns();
        $replacements['columnCount'] = $this->getColumnCount();
        $replacements['actionButtons'] = $this->generateActionButtons();

        if ($this->hasSoftDeletes()) {
            $replacements['trashedFilter'] = $this->generateTrashedFilter();
            $replacements['trashedRowClass'] = $this->generateTrashedRowClass();
        } else {
            $replacements['trashedFilter'] = '';
            $replacements['trashedRowClass'] = '';
        }

        return $replacements;
    }

    protected function getFormViewReplacements(): array
    {
        $replacements = $this->getCommonReplacements();
        $replacements['formFields'] = $this->generateFormFields();

        return $replacements;
    }

    protected function generateProperties(): string
    {
        $properties = [];
        $formColumns = $this->getFormColumns();

        foreach ($formColumns as $name => $column) {
            $type = $this->getPhpType($column['type']);
            $default = $this->getDefaultValue($column);
            $properties[] = "    public {$type} \${$name} = {$default};";
        }

        return implode("\n", $properties);
    }

    protected function generateRules(): string
    {
        $rules = [];
        $formColumns = $this->getFormColumns();

        foreach ($formColumns as $name => $column) {
            $fieldRules = $this->generateRulesForColumn($column);
            $rules[] = "            '{$name}' => [" . implode(', ', array_map(fn ($r) => "'{$r}'", $fieldRules)) . "],";
        }

        return implode("\n", $rules);
    }

    protected function generateRulesForColumn(array $column): array
    {
        $rules = [];

        if (!$column['nullable']) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        switch ($column['type']) {
            case 'string':
                $rules[] = 'string';
                if ($column['length']) {
                    $rules[] = "max:{$column['length']}";
                }
                break;
            case 'text':
                $rules[] = 'string';
                break;
            case 'integer':
            case 'bigInteger':
                $rules[] = 'integer';
                break;
            case 'boolean':
                $rules[] = 'boolean';
                break;
            case 'date':
            case 'dateTime':
                $rules[] = 'date';
                break;
        }

        return $rules;
    }

    protected function generateFillableFields(): string
    {
        $fields = [];
        $formColumns = $this->getFormColumns();

        foreach ($formColumns as $name => $column) {
            $fields[] = "                '{$name}',";
        }

        return implode("\n", $fields);
    }

    protected function generateTableHeaders(): string
    {
        $css = $this->getCssFramework();
        $headers = [];
        $displayColumns = $this->getDisplayColumns();

        foreach ($displayColumns as $column) {
            $label = $this->humanize($column);
            if ($css === 'tailwind') {
                $headers[] = <<<HTML
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer" wire:click="sortBy('{$column}')">
                        <div class="flex items-center gap-1">
                            {$label}
                            @if(\$sortField === '{$column}')
                                @if(\$sortDirection === 'asc')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                @endif
                            @endif
                        </div>
                    </th>
HTML;
            } else {
                $headers[] = "                    <th wire:click=\"sortBy('{$column}')\" style=\"cursor: pointer;\">{$label}</th>";
            }
        }

        return implode("\n", $headers);
    }

    protected function generateTableColumns(): string
    {
        $css = $this->getCssFramework();
        $columns = [];
        $displayColumns = $this->getDisplayColumns();
        $variable = $this->getModelVariable();

        foreach ($displayColumns as $column) {
            if ($css === 'tailwind') {
                $columns[] = <<<HTML
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ \${$variable}->{$column} }}
                        </td>
HTML;
            } else {
                $columns[] = "                        <td>{{ \${$variable}->{$column} }}</td>";
            }
        }

        return implode("\n", $columns);
    }

    protected function generateFormFields(): string
    {
        $css = $this->getCssFramework();
        $fields = [];
        $formColumns = $this->getFormColumns();

        foreach ($formColumns as $name => $column) {
            $label = $this->humanize($name);
            $type = $this->getInputType($column);

            if ($css === 'tailwind') {
                $fields[] = $this->generateTailwindFormField($name, $label, $type, $column);
            } else {
                $fields[] = $this->generateBootstrapFormField($name, $label, $type, $column);
            }
        }

        return implode("\n\n", $fields);
    }

    protected function generateTailwindFormField(string $name, string $label, string $type, array $column): string
    {
        $inputClass = "rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500 w-full";

        if ($type === 'textarea') {
            return <<<HTML
        <div class="mb-4">
            <label for="{$name}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{$label}</label>
            <textarea wire:model="{$name}" id="{$name}" rows="4" class="{$inputClass} @error('{$name}') border-red-500 @enderror"></textarea>
            @error('{$name}')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ \$message }}</p>
            @enderror
        </div>
HTML;
        }

        if ($type === 'checkbox') {
            return <<<HTML
        <div class="mb-4">
            <label class="flex items-center">
                <input type="checkbox" wire:model="{$name}" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{$label}</span>
            </label>
            @error('{$name}')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ \$message }}</p>
            @enderror
        </div>
HTML;
        }

        return <<<HTML
        <div class="mb-4">
            <label for="{$name}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{$label}</label>
            <input type="{$type}" wire:model="{$name}" id="{$name}" class="{$inputClass} @error('{$name}') border-red-500 @enderror">
            @error('{$name}')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ \$message }}</p>
            @enderror
        </div>
HTML;
    }

    protected function generateBootstrapFormField(string $name, string $label, string $type, array $column): string
    {
        if ($type === 'textarea') {
            return <<<HTML
        <div class="mb-3">
            <label for="{$name}" class="form-label">{$label}</label>
            <textarea wire:model="{$name}" id="{$name}" rows="4" class="form-control @error('{$name}') is-invalid @enderror"></textarea>
            @error('{$name}')
                <div class="invalid-feedback">{{ \$message }}</div>
            @enderror
        </div>
HTML;
        }

        if ($type === 'checkbox') {
            return <<<HTML
        <div class="mb-3 form-check">
            <input type="checkbox" wire:model="{$name}" class="form-check-input @error('{$name}') is-invalid @enderror" id="{$name}">
            <label class="form-check-label" for="{$name}">{$label}</label>
            @error('{$name}')
                <div class="invalid-feedback">{{ \$message }}</div>
            @enderror
        </div>
HTML;
        }

        return <<<HTML
        <div class="mb-3">
            <label for="{$name}" class="form-label">{$label}</label>
            <input type="{$type}" wire:model="{$name}" id="{$name}" class="form-control @error('{$name}') is-invalid @enderror">
            @error('{$name}')
                <div class="invalid-feedback">{{ \$message }}</div>
            @enderror
        </div>
HTML;
    }

    protected function generateTrashedFilter(): string
    {
        $css = $this->getCssFramework();

        if ($css === 'tailwind') {
            return <<<HTML
            <label class="flex items-center gap-2">
                <input type="checkbox" wire:model.live="showTrashed" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">Toon verwijderd</span>
            </label>
HTML;
        }

        return <<<HTML
            <div class="form-check">
                <input type="checkbox" wire:model.live="showTrashed" class="form-check-input" id="showTrashed">
                <label class="form-check-label" for="showTrashed">Toon verwijderd</label>
            </div>
HTML;
    }

    protected function generateTrashedRowClass(): string
    {
        $variable = $this->getModelVariable();
        return " class=\"{{ \${$variable}->trashed() ? 'opacity-50' : '' }}\"";
    }

    protected function generateActionButtons(): string
    {
        $css = $this->getCssFramework();
        $variable = $this->getModelVariable();

        if ($css === 'tailwind') {
            if ($this->hasSoftDeletes()) {
                return <<<HTML
                            @if(\${$variable}->trashed())
                                <button wire:click="restore(\${$variable}->id)" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 mr-3">Herstellen</button>
                                <button wire:click="forceDelete(\${$variable}->id)" wire:confirm="Weet je zeker dat je dit permanent wilt verwijderen?" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Permanent</button>
                            @else
                                <button wire:click="delete(\${$variable})" wire:confirm="Weet je zeker dat je dit wilt verwijderen?" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Verwijderen</button>
                            @endif
HTML;
            }

            return <<<HTML
                            <button wire:click="delete(\${$variable})" wire:confirm="Weet je zeker dat je dit wilt verwijderen?" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Verwijderen</button>
HTML;
        }

        // Bootstrap
        if ($this->hasSoftDeletes()) {
            return <<<HTML
                            @if(\${$variable}->trashed())
                                <button wire:click="restore(\${$variable}->id)" class="btn btn-success btn-sm">Herstellen</button>
                                <button wire:click="forceDelete(\${$variable}->id)" wire:confirm="Weet je zeker dat je dit permanent wilt verwijderen?" class="btn btn-danger btn-sm">Permanent</button>
                            @else
                                <button wire:click="delete(\${$variable})" wire:confirm="Weet je zeker dat je dit wilt verwijderen?" class="btn btn-danger btn-sm">Verwijderen</button>
                            @endif
HTML;
        }

        return <<<HTML
                            <button wire:click="delete(\${$variable})" wire:confirm="Weet je zeker dat je dit wilt verwijderen?" class="btn btn-danger btn-sm">Verwijderen</button>
HTML;
    }

    protected function generateSoftDeleteMethods(): string
    {
        $model = $this->modelName;
        $variable = $this->getModelVariable();

        return <<<PHP

    public function restore(int \$id): void
    {
        \${$variable} = {$model}::onlyTrashed()->findOrFail(\$id);
        \${$variable}->restore();
        session()->flash('success', '{$model} succesvol hersteld.');
    }

    public function forceDelete(int \$id): void
    {
        \${$variable} = {$model}::onlyTrashed()->findOrFail(\$id);
        \${$variable}->forceDelete();
        session()->flash('success', '{$model} permanent verwijderd.');
    }
PHP;
    }

    protected function getFormColumns(): array
    {
        if (empty($this->columns)) {
            return [];
        }

        return array_filter(
            $this->columns,
            fn ($col, $name) => !in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at', 'remember_token']),
            ARRAY_FILTER_USE_BOTH
        );
    }

    protected function getDisplayColumns(): array
    {
        if (empty($this->columns)) {
            return ['id', 'name', 'created_at'];
        }

        return array_filter(
            array_keys($this->columns),
            fn ($col) => !in_array($col, ['password', 'remember_token', 'deleted_at'])
        );
    }

    protected function getColumnCount(): int
    {
        return count($this->getDisplayColumns()) + 1;
    }

    protected function getPhpType(string $type): string
    {
        switch ($type) {
            case 'boolean':
                return 'bool';
            case 'integer':
            case 'bigInteger':
                return 'int';
            case 'decimal':
            case 'float':
                return 'float';
            case 'json':
                return 'array';
            default:
                return 'string';
        }
    }

    protected function getDefaultValue(array $column): string
    {
        if ($column['nullable']) {
            return 'null';
        }

        switch ($column['type']) {
            case 'boolean':
                return 'false';
            case 'integer':
            case 'bigInteger':
                return '0';
            case 'decimal':
            case 'float':
                return '0.0';
            case 'json':
                return '[]';
            default:
                return "''";
        }
    }

    protected function getInputType(array $column): string
    {
        $name = $column['name'];
        $type = $column['type'];

        if (Str::contains($name, 'email')) {
            return 'email';
        }

        if (Str::contains($name, 'password')) {
            return 'password';
        }

        switch ($type) {
            case 'text':
                return 'textarea';
            case 'boolean':
                return 'checkbox';
            case 'date':
                return 'date';
            case 'dateTime':
                return 'datetime-local';
            case 'integer':
            case 'bigInteger':
            case 'decimal':
            case 'float':
                return 'number';
            default:
                return 'text';
        }
    }

    protected function humanize(string $name): string
    {
        return Str::title(str_replace(['_', '-'], ' ', $name));
    }
}
