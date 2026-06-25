<?php

namespace App\Http\Requests\Concerns;

use App\Models\CustomField;
use Illuminate\Validation\Rule;

trait ValidatesCustomFields
{
    /**
     * Build validation rules for tenant-defined custom fields of an entity.
     *
     * @return array<string, mixed>
     */
    protected function customFieldRules(string $entityType): array
    {
        $rules = ['custom_fields' => ['nullable', 'array']];

        $fields = CustomField::query()
            ->where('entity_type', $entityType)
            ->get();

        foreach ($fields as $field) {
            $key = "custom_fields.{$field->key}";
            $fieldRules = [$field->is_required ? 'required' : 'nullable'];

            $fieldRules[] = match ($field->type) {
                'number' => 'numeric',
                'date' => 'date',
                'checkbox' => 'boolean',
                'select' => Rule::in($field->options ?? []),
                'textarea' => 'string',
                default => 'string',
            };

            $rules[$key] = $fieldRules;
        }

        return $rules;
    }

    /**
     * Indonesian messages for required custom fields.
     *
     * @return array<string, string>
     */
    protected function customFieldMessages(string $entityType): array
    {
        $messages = [];

        CustomField::query()
            ->where('entity_type', $entityType)
            ->where('is_required', true)
            ->get()
            ->each(function (CustomField $field) use (&$messages): void {
                $messages["custom_fields.{$field->key}.required"] = "{$field->label} wajib diisi.";
            });

        return $messages;
    }
}
