<?php

declare(strict_types=1);

namespace Adedsec\NovaTranslatedLabels\Tests\Fixtures\Nova;

use Adedsec\NovaTranslatedLabels\Tests\Fixtures\Enums\PostStatus;
use Adedsec\NovaTranslatedLabels\Tests\Fixtures\Models\Post as PostModel;
use Adedsec\NovaTranslatedLabels\Tests\Fixtures\Nova\Actions\PublishPost;
use Adedsec\NovaTranslatedLabels\Tests\Fixtures\Nova\Filters\StatusFilter;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * @extends BaseResource<PostModel>
 */
final class Post extends BaseResource
{
    /** @var class-string<PostModel> */
    public static $model = PostModel::class;

    /** @var string */
    public static $group = 'Content';

    /**
     * @return array<int, mixed>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make(),
            Text::make('Title'),
            Select::make('Status')->options(PostStatus::class),
            Badge::make('Stage', 'stage')->map(['draft' => 'warning', 'published' => 'success']),
            BelongsTo::make('Author', 'author', User::class),
            HasMany::make('Comments', 'comments', Comment::class),
            Text::make('Word Count', fn (): int => 0),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function actions(NovaRequest $request): array
    {
        return [new PublishPost];
    }

    /**
     * @return array<int, mixed>
     */
    public function filters(NovaRequest $request): array
    {
        return [new StatusFilter];
    }
}
