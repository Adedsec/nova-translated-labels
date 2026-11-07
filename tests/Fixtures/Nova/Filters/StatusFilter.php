<?php

declare(strict_types=1);

namespace Adedsec\NovaTranslatedLabels\Tests\Fixtures\Nova\Filters;

use Adedsec\NovaTranslatedLabels\Concerns\TranslatesFilterLabels;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

final class StatusFilter extends Filter
{
    use TranslatesFilterLabels;

    /** @var string */
    public $name = 'Status';

    public function apply(NovaRequest $request, Builder $query, mixed $value): Builder
    {
        return $query->where('status', $value);
    }

    /**
     * @return array<string, string>
     */
    public function options(NovaRequest $request): array
    {
        return ['Draft' => 'draft', 'Published' => 'published'];
    }
}
