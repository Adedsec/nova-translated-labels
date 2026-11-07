<?php

declare(strict_types=1);

namespace Adedsec\NovaTranslatedLabels\Tests\Fixtures\Nova\Actions;

use Adedsec\NovaTranslatedLabels\Concerns\TranslatesActionFields;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

final class PublishPost extends Action
{
    use TranslatesActionFields;

    /**
     * @return array<int, mixed>
     */
    public function fields(NovaRequest $request): array
    {
        return [Text::make('Reason')->rules('required')];
    }
}
