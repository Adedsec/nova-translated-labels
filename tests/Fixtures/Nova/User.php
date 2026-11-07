<?php

declare(strict_types=1);

namespace Adedsec\NovaTranslatedLabels\Tests\Fixtures\Nova;

use Adedsec\NovaTranslatedLabels\Tests\Fixtures\Models\User as UserModel;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * @extends BaseResource<UserModel>
 */
final class User extends BaseResource
{
    /** @var class-string<UserModel> */
    public static $model = UserModel::class;

    /** @var string */
    public static $group = 'People';

    /**
     * @return array<int, mixed>
     */
    public function fields(NovaRequest $request): array
    {
        return [ID::make(), Text::make('Name')];
    }
}
