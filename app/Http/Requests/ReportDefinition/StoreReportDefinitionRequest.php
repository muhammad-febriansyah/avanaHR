<?php

namespace App\Http\Requests\ReportDefinition;

use App\Support\ReportSources;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('report.view');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $source = $this->input('source');
        $allowed = array_keys(ReportSources::columns($source));

        return [
            'name' => ['required', 'string', 'max:255'],
            'source' => ['required', Rule::in(ReportSources::keys())],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => [Rule::in($allowed)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama laporan wajib diisi.',
            'source.required' => 'Sumber data wajib dipilih.',
            'columns.required' => 'Pilih minimal satu kolom.',
            'columns.min' => 'Pilih minimal satu kolom.',
        ];
    }
}
