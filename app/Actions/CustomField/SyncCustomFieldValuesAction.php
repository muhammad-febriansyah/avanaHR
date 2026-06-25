<?php

namespace App\Actions\CustomField;

use App\Models\CustomField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\HasRelationships;

class SyncCustomFieldValuesAction
{
    /**
     * Upsert the given custom field values onto a model, keyed by field key.
     *
     * @param  Model&HasRelationships  $model
     * @param  array<string, mixed>  $values  map of custom field key => value
     */
    public function handle(Model $model, string $entityType, array $values): void
    {
        if ($values === []) {
            return;
        }

        $definitions = CustomField::query()
            ->where('entity_type', $entityType)
            ->get()
            ->keyBy('key');

        foreach ($values as $key => $value) {
            $definition = $definitions->get($key);

            if ($definition === null) {
                continue;
            }

            // Empty value clears any stored value instead of persisting a blank row.
            if ($value === null || $value === '') {
                $model->customFieldValues()
                    ->where('custom_field_id', $definition->id)
                    ->delete();

                continue;
            }

            $model->customFieldValues()->updateOrCreate(
                ['custom_field_id' => $definition->id],
                ['value' => $value],
            );
        }
    }
}
