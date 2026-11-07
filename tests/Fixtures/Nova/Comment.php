<?php

declare(strict_types=1);

namespace Adedsec\NovaTranslatedLabels\Tests\Fixtures\Nova;

use Adedsec\NovaTranslatedLabels\Tests\Fixtures\Models\Comment as CommentModel;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * @extends BaseResource<CommentModel>
 */
final class Comment extends BaseResource
{
    /** @var class-string<CommentModel> */
    public static $model = CommentModel::class;

    /**
     * @return array<int, mixed>
     */
    public function fields(NovaRequest $request): array
    {
        return [ID::make(), Textarea::make('Body')];
    }
}
