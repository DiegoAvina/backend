<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\RejectsUnknownQueryParameters;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Parámetros comunes de los listados públicos: paginación y orden.
 *
 * `sort` acepta solo columnas de una lista blanca; el prefijo `-` invierte el
 * orden (p. ej. `-name`). Parámetros desconocidos se rechazan con 422.
 */
class ListContentRequest extends FormRequest
{
    use RejectsUnknownQueryParameters;

    public const DEFAULT_PER_PAGE = 20;

    public const MAX_PER_PAGE = 50;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
            'sort' => ['sometimes', 'string', Rule::in($this->sortOptions())],
            ...$this->filterRules(),
        ];
    }

    /**
     * Reglas de los filtros propios de cada listado.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [];
    }

    /**
     * Columnas por las que se puede ordenar.
     *
     * @return list<string>
     */
    protected function sortableColumns(): array
    {
        return ['sort_order', 'name'];
    }

    protected function defaultSort(): string
    {
        return 'sort_order';
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', self::DEFAULT_PER_PAGE);
    }

    /**
     * Aplica el orden solicitado y un desempate estable por `id`.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function applySort(Builder $query): Builder
    {
        $sort = (string) $this->validated('sort', $this->defaultSort());
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        return $query
            ->orderBy($query->qualifyColumn($column), $direction)
            ->orderBy($query->qualifyColumn('id'));
    }

    /**
     * @return list<string>
     */
    private function sortOptions(): array
    {
        $columns = $this->sortableColumns();

        return [...$columns, ...array_map(fn (string $column): string => '-'.$column, $columns)];
    }
}
