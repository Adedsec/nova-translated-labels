<?php

declare(strict_types=1);

namespace Adedsec\NovaTranslatedLabels\Tests\Fixtures\Broken;

use Adedsec\NovaTranslatedLabels\Tests\Fixtures\Models\User as UserModel;
use Adedsec\NovaTranslatedLabels\Tests\Fixtures\Nova\BaseResource;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use RuntimeException;

/**
 * A resource whose fields need a signed-in user, which the walk does not have without --user.
 *
 * @extends BaseResource<UserModel>
 */
final class Profile extends BaseResource
{
    /** @var class-string<UserModel> */
    public static $model = UserModel::class;

    /**
     * @return array<int, mixed>
     */
    public function fields(NovaRequest $request): array
    {
        if ($request->user() === null) {
            throw new RuntimeException('No signed-in user.');
        }

        return [Text::make('Name')];
    }
}
