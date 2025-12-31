<?php

namespace AutoCrud\Services;

use Illuminate\Support\Str;

class ValidationRuleGenerator
{
    protected SchemaAnalyzer $schemaAnalyzer;
    protected string $table;
    protected string $modelName;

    public function __construct(SchemaAnalyzer $schemaAnalyzer, string $table, string $modelName)
    {
        $this->schemaAnalyzer = $schemaAnalyzer;
        $this->table = $table;
        $this->modelName = $modelName;
    }

    /**
     * Generate validation rules for store (create) requests.
     */
    public function generateStoreRules(): array
    {
        $columns = $this->schemaAnalyzer->getFillableColumns();
        $rules = [];

        foreach ($columns as $column) {
            $rules[$column['name']] = $this->generateRulesForColumn($column, false);
        }

        return $rules;
    }

    /**
     * Generate validation rules for update requests.
     */
    public function generateUpdateRules(): array
    {
        $columns = $this->schemaAnalyzer->getFillableColumns();
        $rules = [];

        foreach ($columns as $column) {
            $rules[$column['name']] = $this->generateRulesForColumn($column, true);
        }

        return $rules;
    }

    /**
     * Generate rules for a single column.
     */
    protected function generateRulesForColumn(array $column, bool $isUpdate): array
    {
        $rules = [];

        // Required/nullable
        if (!$column['nullable'] && $column['default'] === null) {
            $rules[] = $isUpdate ? 'sometimes' : 'required';
        } else {
            $rules[] = 'nullable';
        }

        // Type-based rules
        $typeRules = $this->getTypeRules($column);
        $rules = array_merge($rules, $typeRules);

        // Length constraint
        if ($column['length'] && in_array($column['type'], ['string'])) {
            $rules[] = "max:{$column['length']}";
        }

        // Unique constraint
        if ($column['is_unique']) {
            $uniqueRule = "unique:{$this->table},{$column['name']}";
            if ($isUpdate) {
                // Ignore current record when updating
                $uniqueRule .= ',\' . $this->' . Str::camel($this->modelName) . '->id';
            }
            $rules[] = $uniqueRule;
        }

        // Foreign key constraint
        if ($column['is_foreign']) {
            $relatedTable = Str::plural(Str::beforeLast($column['name'], '_id'));
            $rules[] = "exists:{$relatedTable},id";
        }

        return $rules;
    }

    /**
     * Get validation rules based on column type.
     */
    protected function getTypeRules(array $column): array
    {
        $rules = [];

        switch ($column['type']) {
            case 'string':
                $rules[] = 'string';
                // Check for email pattern
                if (Str::contains($column['name'], 'email')) {
                    $rules[] = 'email';
                }
                // Check for URL pattern
                if (Str::contains($column['name'], ['url', 'link', 'website'])) {
                    $rules[] = 'url';
                }
                break;

            case 'text':
                $rules[] = 'string';
                break;

            case 'integer':
            case 'bigInteger':
                $rules[] = 'integer';
                if ($column['unsigned']) {
                    $rules[] = 'min:0';
                }
                break;

            case 'decimal':
            case 'float':
                $rules[] = 'numeric';
                if ($column['unsigned']) {
                    $rules[] = 'min:0';
                }
                break;

            case 'boolean':
                $rules[] = 'boolean';
                break;

            case 'date':
                $rules[] = 'date';
                break;

            case 'dateTime':
            case 'dateTimeTz':
                $rules[] = 'date';
                break;

            case 'time':
                $rules[] = 'date_format:H:i:s';
                break;

            case 'json':
                $rules[] = 'array';
                break;

            case 'uuid':
                $rules[] = 'uuid';
                break;

            case 'binary':
                $rules[] = 'string';
                break;
        }

        // Custom field patterns
        $rules = array_merge($rules, $this->getPatternRules($column['name']));

        return $rules;
    }

    /**
     * Get rules based on common field name patterns.
     */
    protected function getPatternRules(string $fieldName): array
    {
        $patterns = [
            'password' => ['min:8'],
            'phone' => ['regex:/^[\+]?[(]?[0-9]{1,3}[)]?[-\s\.]?[0-9]{1,4}[-\s\.]?[0-9]{1,4}[-\s\.]?[0-9]{1,9}$/'],
            'zip' => ['regex:/^[0-9]{4,10}$/'],
            'postal' => ['regex:/^[0-9]{4,10}$/'],
            'slug' => ['alpha_dash'],
            'image' => ['image', 'max:2048'],
            'avatar' => ['image', 'max:2048'],
            'photo' => ['image', 'max:2048'],
            'file' => ['file', 'max:10240'],
            'document' => ['file', 'max:10240'],
            'ip' => ['ip'],
            'ip_address' => ['ip'],
            'mac' => ['mac_address'],
            'color' => ['regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'hex_color' => ['regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        ];

        foreach ($patterns as $pattern => $rules) {
            if (Str::contains(strtolower($fieldName), $pattern)) {
                return $rules;
            }
        }

        return [];
    }

    /**
     * Format rules array to string representation for stub.
     */
    public function formatRulesForStub(array $rules): string
    {
        $formatted = [];

        foreach ($rules as $field => $fieldRules) {
            $rulesString = $this->formatFieldRules($fieldRules);
            $formatted[] = "            '{$field}' => [{$rulesString}],";
        }

        return implode("\n", $formatted);
    }

    /**
     * Format field rules to string.
     */
    protected function formatFieldRules(array $rules): string
    {
        $formattedRules = array_map(function ($rule) {
            // Check if it's a dynamic rule (contains PHP code)
            if (Str::contains($rule, "' . ")) {
                return $rule;
            }
            return "'{$rule}'";
        }, $rules);

        return implode(', ', $formattedRules);
    }

    /**
     * Generate validation messages for common rules.
     */
    public function generateMessages(): array
    {
        return [
            'required' => 'Het :attribute veld is verplicht.',
            'string' => 'Het :attribute veld moet tekst zijn.',
            'email' => 'Het :attribute veld moet een geldig e-mailadres zijn.',
            'unique' => 'Deze :attribute is al in gebruik.',
            'exists' => 'De geselecteerde :attribute is ongeldig.',
            'integer' => 'Het :attribute veld moet een geheel getal zijn.',
            'numeric' => 'Het :attribute veld moet een nummer zijn.',
            'boolean' => 'Het :attribute veld moet waar of onwaar zijn.',
            'date' => 'Het :attribute veld moet een geldige datum zijn.',
            'max.string' => 'Het :attribute veld mag niet meer dan :max karakters bevatten.',
            'max.file' => 'Het :attribute bestand mag niet groter zijn dan :max kilobytes.',
            'min.string' => 'Het :attribute veld moet minstens :min karakters bevatten.',
        ];
    }
}
