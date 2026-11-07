<?php

declare(strict_types=1);

namespace Adedsec\NovaTranslatedLabels\Concerns;

use Adedsec\NovaTranslatedLabels\FieldDecorator;
use Adedsec\NovaTranslatedLabels\Label;
use Adedsec\NovaTranslatedLabels\Translator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionCollection;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\FieldCollection;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;

/**
 * Use on your base Nova resource (App\Nova\Resource) to translate every resource.
 *
 * @mixin \Laravel\Nova\Resource<Model>
 */
trait HasTranslatedLabels
{
    public static function group(): string
    {
        $group = (string) parent::group();
        $translator = resolve(Translator::class);

        return $translator->subtitle('groups', $translator->translate('groups', $group), $group, $group);
    }

    public static function label(): string
    {
        $translator = resolve(Translator::class);
        $original = (string) parent::label();
        $label = $translator->resource(static::uriKey(), 'label', $original);

        return $translator->subtitle('resources', $label, $original, static::newModel()->getTable());
    }

    public static function singularLabel(): string
    {
        $translator = resolve(Translator::class);
        $plural = Label::strip(static::label());

        return $translator->resource(
            static::uriKey(),
            'singular',
            $translator->isTranslated($plural) ? $plural : Str::singular($plural),
        );
    }

    public function availableFields(NovaRequest $request): FieldCollection
    {
        return $this->withTranslatedLabels(parent::availableFields($request));
    }

    /**
     * @param  array<int, string>  $methods
     */
    public function buildAvailableFields(NovaRequest $request, array $methods): FieldCollection
    {
        return $this->withTranslatedLabels(parent::buildAvailableFields($request, $methods));
    }

    public function resolveActions(NovaRequest $request): ActionCollection
    {
        $translator = resolve(Translator::class);

        return parent::resolveActions($request)->each(static function (Action $action) use ($translator): void {
            $name = (string) $action->name();

            if ($translator->isTranslated($name)) {
                return;
            }

            // Nova derives the key it runs an action by from its name, so pin it before renaming.
            $action->uriKey ??= $action->uriKey();
            $action->name = $translator->translate('actions', $name, static::uriKey());
        });
    }

    /**
     * @param  FieldCollection<int, Field>  $fields
     * @return FieldCollection<int, Field>
     */
    private function withTranslatedLabels(FieldCollection $fields): FieldCollection
    {
        resolve(FieldDecorator::class)->decorate(
            $fields,
            $this->resource instanceof Model ? $this->resource : static::newModel(),
            static::uriKey(),
        );

        return $fields;
    }
}
