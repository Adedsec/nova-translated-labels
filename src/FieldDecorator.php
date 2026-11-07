<?php

declare(strict_types=1);

namespace Adedsec\NovaTranslatedLabels;

use BackedEnum;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo as BelongsToRelation;
use Illuminate\Database\Eloquent\Relations\Relation;
use Laravel\Nova\Contracts\RelatableField;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\BooleanGroup;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\MultiSelect;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Nova;
use Laravel\Nova\Panel;
use Stringable;
use Throwable;

final class FieldDecorator
{
    private const DECORATED = 'novaTranslatedLabels';

    public function __construct(private readonly Translator $translator) {}

    /**
     * @param  iterable<mixed>  $fields
     */
    public function decorate(iterable $fields, ?Model $model, ?string $uriKey, bool $withRealNames = true): void
    {
        foreach ($fields as $field) {
            if ($field instanceof Field && ! ($field->meta[self::DECORATED] ?? false)) {
                $this->decorateField($field, $model, $uriKey, $withRealNames);
            }
        }
    }

    private function decorateField(Field $field, ?Model $model, ?string $uriKey, bool $withRealNames): void
    {
        $field->withMeta([self::DECORATED => true]);

        $original = (string) $field->name;
        $label = $this->translator->translate('fields', $original, $uriKey);

        if ($field instanceof BelongsTo && $field->singularLabel === $original) {
            $field->singularLabel = $label;
        }

        $field->name = $this->translator->subtitle('fields', $label, $original, $withRealNames ? $this->realName($field, $model) : null);

        if ($field->panel instanceof Panel) {
            $field->panel->name = $this->translator->translate('panels', (string) $field->panel->name, $uriKey);
        }

        match (true) {
            $field instanceof Select, $field instanceof MultiSelect, $field instanceof BooleanGroup => $this->decorateOptions($field, $uriKey),
            $field instanceof Badge => $this->decorateBadge($field, $uriKey),
            default => null,
        };
    }

    private function realName(Field $field, ?Model $model): ?string
    {
        $attribute = $field->attribute;

        if ($attribute === '' || $attribute === 'ComputedField') {
            return null;
        }

        $relation = $field instanceof RelatableField || $field instanceof BelongsToMany ? $this->relation($model, $attribute) : null;

        return match (true) {
            $relation instanceof BelongsToRelation => $relation->getForeignKeyName(),
            $relation instanceof Relation => $relation->getRelated()->getTable(),
            default => $attribute,
        };
    }

    /**
     * @return Relation<Model, Model, mixed>|null
     */
    private function relation(?Model $model, string $attribute): ?Relation
    {
        if (! $model instanceof Model || ! $model->isRelation($attribute)) {
            return null;
        }

        try {
            $relation = $model->{$attribute}();
        } catch (Throwable) {
            return null;
        }

        return $relation instanceof Relation ? $relation : null;
    }

    private function decorateOptions(Select|MultiSelect|BooleanGroup $field, ?string $uriKey): void
    {
        $original = $field->optionsCallback;

        if ($original === null) {
            return;
        }

        $field->optionsCallback = function () use ($field, $original, $uriKey): array {
            $options = collect($this->resolveOptions($original));

            // Long lists are database rows, not fixed labels; a list-shaped BooleanGroup saves each label as its key.
            if ($options->count() > $this->translator->maxStaticOptions() || ($field instanceof BooleanGroup && array_is_list($options->all()))) {
                return $options->all();
            }

            return $options
                ->map(fn (mixed $label, int|string $value): mixed => match (true) {
                    is_array($label) && isset($label['label']) => [...$label, 'label' => $this->option($label['label'], $value, $uriKey)],
                    is_string($label), $label instanceof Stringable => $this->option($label, $value, $uriKey),
                    default => $label,
                })
                ->all();
        };
    }

    private function decorateBadge(Badge $field, ?string $uriKey): void
    {
        $values = array_unique([...array_keys($field->map), ...array_keys($field->labels)]);

        foreach ($values as $value) {
            $field->labels[$value] = $this->translator->translate('options', (string) ($field->labels[$value] ?? $value), $uriKey);
        }
    }

    private function option(mixed $label, int|string $value, ?string $uriKey): string
    {
        $text = is_scalar($label) || $label instanceof Stringable ? (string) $label : '';
        $translated = $this->translator->translate('options', $text, $uriKey);

        return $this->translator->subtitle('options', $translated, $text, is_string($value) ? $value : null);
    }

    /**
     * @return iterable<int|string, mixed>
     */
    private function resolveOptions(mixed $options): iterable
    {
        $options = $options instanceof Closure ? $options() : $options;

        if (is_string($options) && enum_exists($options) && is_subclass_of($options, BackedEnum::class)) {
            return collect($options::cases())
                ->mapWithKeys(static fn (BackedEnum $case): array => [$case->value => Nova::humanize($case)]);
        }

        if (is_callable($options)) {
            $options = $options();
        }

        return is_iterable($options) ? $options : [];
    }
}
