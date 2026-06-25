<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportDefinition\StoreReportDefinitionRequest;
use App\Models\ReportDefinition;
use App\Support\ReportSources;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportBuilderController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('report.view'), 403);

        $definitions = ReportDefinition::query()
            ->orderByDesc('id')
            ->paginate(15)
            ->through(fn (ReportDefinition $definition): array => [
                'id' => $definition->id,
                'name' => $definition->name,
                'source' => $definition->source,
                'source_label' => ReportSources::all()[$definition->source]['label'] ?? $definition->source,
                'columns_count' => count($definition->columns ?? []),
                'created_at' => $definition->created_at?->format('Y-m-d H:i'),
            ]);

        return Inertia::render('report-builder/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Report Builder', 'href' => route('report-builder.index')],
            ],
            'definitions' => $definitions,
            'sources' => $this->sourceCatalog(),
        ]);
    }

    public function store(StoreReportDefinitionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Keep only known columns, in the source's canonical order.
        $allowed = array_keys(ReportSources::columns($data['source']));
        $columns = array_values(array_filter($allowed, fn (string $column): bool => in_array($column, $data['columns'], true)));

        $definition = ReportDefinition::create([
            'name' => $data['name'],
            'source' => $data['source'],
            'columns' => $columns,
            'created_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Laporan berhasil disimpan.']);

        return to_route('report-builder.run', $definition);
    }

    public function run(Request $request, ReportDefinition $reportDefinition): Response
    {
        abort_unless($request->user()->can('report.view'), 403);
        abort_unless(ReportSources::has($reportDefinition->source), 404);

        $columnMeta = ReportSources::columns($reportDefinition->source);
        $columns = array_values(array_filter(
            $reportDefinition->columns ?? [],
            fn (string $column): bool => array_key_exists($column, $columnMeta),
        ));

        $rows = ReportSources::query($reportDefinition->source)
            ->orderByDesc('id')
            ->paginate(20)
            ->through(fn (Model $record): array => collect($columns)
                ->mapWithKeys(fn (string $column): array => [$column => $this->stringify($record->{$column})])
                ->all());

        return Inertia::render('report-builder/run', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Report Builder', 'href' => route('report-builder.index')],
                ['title' => $reportDefinition->name, 'href' => route('report-builder.run', $reportDefinition)],
            ],
            'report' => [
                'id' => $reportDefinition->id,
                'name' => $reportDefinition->name,
                'source_label' => ReportSources::all()[$reportDefinition->source]['label'] ?? $reportDefinition->source,
            ],
            'columns' => array_map(fn (string $column): array => [
                'key' => $column,
                'label' => $columnMeta[$column],
            ], $columns),
            'rows' => $rows,
        ]);
    }

    public function destroy(Request $request, ReportDefinition $reportDefinition): RedirectResponse
    {
        abort_unless($request->user()->can('report.view'), 403);

        $reportDefinition->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Laporan berhasil dihapus.']);

        return to_route('report-builder.index');
    }

    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return '-';
        }

        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        return (string) $value;
    }

    /**
     * @return list<array{value: string, label: string, columns: list<array{value: string, label: string}>}>
     */
    private function sourceCatalog(): array
    {
        return collect(ReportSources::all())
            ->map(fn (array $source, string $key): array => [
                'value' => $key,
                'label' => $source['label'],
                'columns' => collect($source['columns'])
                    ->map(fn (string $label, string $column): array => ['value' => $column, 'label' => $label])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }
}
