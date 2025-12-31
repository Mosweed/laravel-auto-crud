<?php

namespace AutoCrud\Generators;

use Illuminate\Support\Str;

class ViewGenerator extends BaseGenerator
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
        $css = $this->getCssFramework();
        $views = ['index', 'create', 'edit', 'show'];

        foreach ($views as $view) {
            $results[$view] = $this->generateView($view, $css);
        }

        return $results;
    }

    protected function generateView(string $view, string $css): array
    {
        $stubPath = "views/{$css}/{$view}.blade.stub";
        $content = $this->getStubContent($stubPath);
        $content = $this->replacePlaceholders($content, $this->getReplacements($view));

        $viewPath = config('auto-crud.paths.views') . '/' . $this->getViewPath();
        $path = $viewPath . '/' . $view . '.blade.php';
        $created = $this->createFile($path, $content);

        return [
            'path' => $path,
            'created' => $created,
        ];
    }

    protected function getReplacements(string $view): array
    {
        $replacements = $this->getCommonReplacements();

        switch ($view) {
            case 'index':
                $replacements['tableHeaders'] = $this->generateTableHeaders();
                $replacements['tableColumns'] = $this->generateTableColumns();
                $replacements['columnCount'] = $this->getColumnCount();
                $replacements['trashedFilter'] = $this->hasSoftDeletes() ? $this->generateTrashedFilter() : '';
                $replacements['trashedRowClass'] = $this->hasSoftDeletes() ? $this->generateTrashedRowClass() : '';
                $replacements['actionButtons'] = $this->generateIndexActionButtons();
                break;

            case 'create':
            case 'edit':
                $replacements['formFields'] = $this->generateFormFields($view === 'edit');
                break;

            case 'show':
                $replacements['detailFields'] = $this->generateDetailFields();
                $replacements['softDeleteInfo'] = $this->hasSoftDeletes() ? $this->generateSoftDeleteInfo() : '';
                $replacements['actionButtons'] = $this->generateShowActionButtons();
                $replacements['relationships'] = $this->generateRelationshipsSection();
                break;
        }

        return $replacements;
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
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {$label}
                                    </th>
HTML;
            } else {
                $headers[] = "                                    <th>{$label}</th>";
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
                $columns[] = "                                        <td>{{ \${$variable}->{$column} }}</td>";
            }
        }

        return implode("\n", $columns);
    }

    protected function generateFormFields(bool $isEdit): string
    {
        $css = $this->getCssFramework();
        $fields = [];
        $formColumns = $this->getFormColumns();
        $variable = $this->getModelVariable();

        foreach ($formColumns as $name => $column) {
            $label = $this->humanize($name);
            $value = $isEdit ? "{{ old('{$name}', \${$variable}->{$name}) }}" : "{{ old('{$name}') }}";
            $required = !$column['nullable'] ? 'required' : '';
            $type = $this->getInputType($column);

            if ($css === 'tailwind') {
                $fields[] = $this->generateTailwindFormField($name, $label, $type, $value, $required, $column);
            } else {
                $fields[] = $this->generateBootstrapFormField($name, $label, $type, $value, $required, $column);
            }
        }

        return implode("\n\n", $fields);
    }

    protected function generateTailwindFormField(string $name, string $label, string $type, string $value, string $required, array $column): string
    {
        $inputClass = "rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500 w-full";

        if ($type === 'textarea') {
            return <<<HTML
                        <div class="mb-4">
                            <label for="{$name}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{$label}</label>
                            <textarea name="{$name}" id="{$name}" rows="4" class="{$inputClass}" {$required}>{$value}</textarea>
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
                                <input type="checkbox" name="{$name}" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" {{ old('{$name}', \${$this->getModelVariable()}->{$name} ?? false) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{$label}</span>
                            </label>
                            @error('{$name}')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ \$message }}</p>
                            @enderror
                        </div>
HTML;
        }

        if ($type === 'select' && ($column['is_foreign'] ?? false)) {
            $relatedModel = Str::studly(Str::beforeLast($name, '_id'));
            $relatedPlural = Str::camel(Str::plural($relatedModel));
            return <<<HTML
                        <div class="mb-4">
                            <label for="{$name}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{$label}</label>
                            <select name="{$name}" id="{$name}" class="{$inputClass}" {$required}>
                                <option value="">Selecteer {$label}</option>
                                @foreach(\${$relatedPlural} as \$item)
                                    <option value="{{ \$item->id }}" {{ old('{$name}', \${$this->getModelVariable()}->{$name} ?? '') == \$item->id ? 'selected' : '' }}>{{ \$item->name ?? \$item->id }}</option>
                                @endforeach
                            </select>
                            @error('{$name}')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ \$message }}</p>
                            @enderror
                        </div>
HTML;
        }

        return <<<HTML
                        <div class="mb-4">
                            <label for="{$name}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{$label}</label>
                            <input type="{$type}" name="{$name}" id="{$name}" value="{$value}" class="{$inputClass}" {$required}>
                            @error('{$name}')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ \$message }}</p>
                            @enderror
                        </div>
HTML;
    }

    protected function generateBootstrapFormField(string $name, string $label, string $type, string $value, string $required, array $column): string
    {
        if ($type === 'textarea') {
            return <<<HTML
                        <div class="mb-3">
                            <label for="{$name}" class="form-label">{$label}</label>
                            <textarea name="{$name}" id="{$name}" rows="4" class="form-control @error('{$name}') is-invalid @enderror" {$required}>{$value}</textarea>
                            @error('{$name}')
                                <div class="invalid-feedback">{{ \$message }}</div>
                            @enderror
                        </div>
HTML;
        }

        if ($type === 'checkbox') {
            return <<<HTML
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="{$name}" value="1" class="form-check-input @error('{$name}') is-invalid @enderror" id="{$name}" {{ old('{$name}', \${$this->getModelVariable()}->{$name} ?? false) ? 'checked' : '' }}>
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
                            <input type="{$type}" name="{$name}" id="{$name}" value="{$value}" class="form-control @error('{$name}') is-invalid @enderror" {$required}>
                            @error('{$name}')
                                <div class="invalid-feedback">{{ \$message }}</div>
                            @enderror
                        </div>
HTML;
    }

    protected function generateDetailFields(): string
    {
        $css = $this->getCssFramework();
        $fields = [];
        $displayColumns = $this->getDisplayColumns();
        $variable = $this->getModelVariable();

        foreach ($displayColumns as $column) {
            $label = $this->humanize($column);

            if ($css === 'tailwind') {
                $fields[] = <<<HTML
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{$label}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ \${$variable}->{$column} }}</dd>
                        </div>
HTML;
            } else {
                $fields[] = <<<HTML
                        <dt class="col-sm-3">{$label}</dt>
                        <dd class="col-sm-9">{{ \${$variable}->{$column} }}</dd>
HTML;
            }
        }

        return implode("\n", $fields);
    }

    protected function generateTrashedFilter(): string
    {
        $css = $this->getCssFramework();

        if ($css === 'tailwind') {
            return <<<HTML
                            <select name="trashed" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Alle items</option>
                                <option value="1" {{ request('trashed') == '1' ? 'selected' : '' }}>Inclusief verwijderd</option>
                                <option value="only" {{ request('trashed') == 'only' ? 'selected' : '' }}>Alleen verwijderd</option>
                            </select>
HTML;
        }

        return <<<HTML
                            <div class="col-md-4">
                                <select name="trashed" class="form-select">
                                    <option value="">Alle items</option>
                                    <option value="1" {{ request('trashed') == '1' ? 'selected' : '' }}>Inclusief verwijderd</option>
                                    <option value="only" {{ request('trashed') == 'only' ? 'selected' : '' }}>Alleen verwijderd</option>
                                </select>
                            </div>
HTML;
    }

    protected function generateTrashedRowClass(): string
    {
        $variable = $this->getModelVariable();
        return " class=\"{{ \${$variable}->trashed() ? 'opacity-50' : '' }}\"";
    }

    protected function generateSoftDeleteInfo(): string
    {
        $css = $this->getCssFramework();
        $variable = $this->getModelVariable();

        if ($css === 'tailwind') {
            return <<<HTML

                    @if(\${$variable}->trashed())
                        <div class="mt-4 p-4 bg-yellow-100 dark:bg-yellow-900 rounded-md">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                Dit item is verwijderd op {{ \${$variable}->deleted_at->format('d-m-Y H:i') }}
                            </p>
                        </div>
                    @endif
HTML;
        }

        return <<<HTML

                    @if(\${$variable}->trashed())
                        <div class="alert alert-warning mt-3">
                            Dit item is verwijderd op {{ \${$variable}->deleted_at->format('d-m-Y H:i') }}
                        </div>
                    @endif
HTML;
    }

    protected function generateIndexActionButtons(): string
    {
        if (!$this->hasSoftDeletes()) {
            $css = $this->getCssFramework();
            $variable = $this->getModelVariable();
            $route = $this->getRouteName();

            if ($css === 'tailwind') {
                return <<<HTML
                                            <form action="{{ route('{$route}.destroy', \${$variable}) }}" method="POST" class="inline" onsubmit="return confirm('Weet je zeker dat je dit wilt verwijderen?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Verwijderen</button>
                                            </form>
HTML;
            }

            return <<<HTML
                                            <form action="{{ route('{$route}.destroy', \${$variable}) }}" method="POST" class="d-inline" onsubmit="return confirm('Weet je zeker dat je dit wilt verwijderen?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">Verwijderen</button>
                                            </form>
HTML;
        }

        return $this->generateSoftDeleteActionButtons();
    }

    protected function generateSoftDeleteActionButtons(): string
    {
        $css = $this->getCssFramework();
        $variable = $this->getModelVariable();
        $route = $this->getRouteName();

        if ($css === 'tailwind') {
            return <<<HTML
                                            @if(\${$variable}->trashed())
                                                <form action="{{ route('{$route}.restore', \${$variable}->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 mr-3">Herstellen</button>
                                                </form>
                                                <form action="{{ route('{$route}.force-delete', \${$variable}->id) }}" method="POST" class="inline" onsubmit="return confirm('Weet je zeker dat je dit permanent wilt verwijderen?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Permanent verwijderen</button>
                                                </form>
                                            @else
                                                <form action="{{ route('{$route}.destroy', \${$variable}) }}" method="POST" class="inline" onsubmit="return confirm('Weet je zeker dat je dit wilt verwijderen?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Verwijderen</button>
                                                </form>
                                            @endif
HTML;
        }

        return <<<HTML
                                            @if(\${$variable}->trashed())
                                                <form action="{{ route('{$route}.restore', \${$variable}->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm">Herstellen</button>
                                                </form>
                                                <form action="{{ route('{$route}.force-delete', \${$variable}->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Weet je zeker dat je dit permanent wilt verwijderen?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">Permanent</button>
                                                </form>
                                            @else
                                                <form action="{{ route('{$route}.destroy', \${$variable}) }}" method="POST" class="d-inline" onsubmit="return confirm('Weet je zeker dat je dit wilt verwijderen?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">Verwijderen</button>
                                                </form>
                                            @endif
HTML;
    }

    protected function generateShowActionButtons(): string
    {
        if (!$this->hasSoftDeletes()) {
            return '';
        }

        $css = $this->getCssFramework();
        $variable = $this->getModelVariable();
        $route = $this->getRouteName();

        if ($css === 'tailwind') {
            return <<<HTML
                        @if(\${$variable}->trashed())
                            <form action="{{ route('{$route}.restore', \${$variable}->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Herstellen
                                </button>
                            </form>
                        @endif
HTML;
        }

        return <<<HTML
                        @if(\${$variable}->trashed())
                            <form action="{{ route('{$route}.restore', \${$variable}->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success">Herstellen</button>
                            </form>
                        @endif
HTML;
    }

    protected function generateRelationshipsSection(): string
    {
        return '';
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

    protected function getColumnCount(): int
    {
        return count($this->getDisplayColumns()) + 1;
    }

    protected function getInputType(array $column): string
    {
        $name = $column['name'];
        $type = $column['type'];

        if ($column['is_foreign'] ?? false) {
            return 'select';
        }

        if (Str::contains($name, 'email')) {
            return 'email';
        }

        if (Str::contains($name, 'password')) {
            return 'password';
        }

        if (Str::contains($name, ['url', 'link', 'website'])) {
            return 'url';
        }

        if (Str::contains($name, 'phone')) {
            return 'tel';
        }

        if (Str::contains($name, 'color')) {
            return 'color';
        }

        switch ($type) {
            case 'text':
                return 'textarea';
            case 'boolean':
                return 'checkbox';
            case 'date':
                return 'date';
            case 'dateTime':
            case 'dateTimeTz':
                return 'datetime-local';
            case 'time':
                return 'time';
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
