<?php

namespace AutoCrud\Generators;

class FieldParser
{
    /**
     * Parse a fields string into an array of field definitions.
     */
    public function parse(string $fieldsString): array
    {
        if (empty($fieldsString)) {
            return [];
        }

        $fields = [];
        $fieldParts = explode(',', $fieldsString);

        foreach ($fieldParts as $field) {
            $parts = explode(':', trim($field));

            if (empty($parts[0])) {
                continue;
            }

            $name = $parts[0];
            $type = $parts[1] ?? 'string';

            // Parse modifiers
            $nullable = false;
            $length = null;
            $unique = false;

            for ($i = 2; $i < count($parts); $i++) {
                $modifier = strtolower($parts[$i]);
                if ($modifier === 'nullable') {
                    $nullable = true;
                } elseif ($modifier === 'unique') {
                    $unique = true;
                } elseif (is_numeric($modifier)) {
                    $length = (int) $modifier;
                }
            }

            // Map common type aliases
            $typeMap = [
                'str' => 'string',
                'int' => 'integer',
                'bool' => 'boolean',
                'txt' => 'text',
            ];

            $mappedType = $typeMap[$type] ?? $type;

            $fields[] = [
                'name' => $name,
                'type' => $mappedType,
                'nullable' => $nullable,
                'unique' => $unique,
                'length' => $length,
            ];
        }

        return $fields;
    }
}
