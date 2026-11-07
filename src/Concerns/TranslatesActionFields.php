<?php

declare(strict_types=1);

namespace Adedsec\NovaTranslatedLabels\Concerns;

use Adedsec\NovaTranslatedLabels\FieldDecorator;
use Adedsec\NovaTranslatedLabels\Label;
use Illuminate\Support\Facades\Validator;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\FieldCollection;
use Laravel\Nova\Http\Requests\ActionRequest;

/**
 * Use on a Nova action to translate the fields of its modal and their validation messages.
 *
 * @mixin Action
 */
trait TranslatesActionFields
{
    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $serialized = parent::jsonSerialize();

        if (is_array($serialized['fields'] ?? null)) {
            resolve(FieldDecorator::class)->decorate($serialized['fields'], null, null, withRealNames: false);
        }

        return $serialized;
    }

    /**
     * Nova validates a fresh copy of the fields, so translate that copy too, or
     * the messages would name the English attribute.
     *
     * @return array<string, mixed>
     */
    public function validateFields(ActionRequest $request): array
    {
        /** @var FieldCollection<int, Field> $fields */
        $fields = FieldCollection::make($this->fields($request))
            ->authorized($request)
            ->applyDependsOn($request)
            ->withoutReadonly($request)
            ->withoutUnfillable();

        resolve(FieldDecorator::class)->decorate($fields, null, null, withRealNames: false);

        return Validator::make(
            $request->all(),
            $fields->mapWithKeys(static fn (Field $field): array => $field->getCreationRules($request))->all(),
            [],
            $fields->reject(static fn (Field $field): bool => empty($field->name))
                ->mapWithKeys(static fn (Field $field): array => [$field->attribute => Label::strip((string) $field->name)])
                ->all(),
        )->after(function ($validator) use ($request): void {
            $this->afterValidation($request, $validator);
        })->validate();
    }
}
