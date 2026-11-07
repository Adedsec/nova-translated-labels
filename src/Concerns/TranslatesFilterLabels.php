<?php

declare(strict_types=1);

namespace Adedsec\NovaTranslatedLabels\Concerns;

use Adedsec\NovaTranslatedLabels\Translator;
use Laravel\Nova\Filters\Filter;

/**
 * Use on a Nova filter to translate its name and options.
 *
 * @mixin Filter
 */
trait TranslatesFilterLabels
{
    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $serialized = parent::jsonSerialize();
        $translator = resolve(Translator::class);

        if (is_string($serialized['name'] ?? null)) {
            $serialized['name'] = $translator->translate('filters', $serialized['name']);
        }

        if (is_array($serialized['options'] ?? null) && count($serialized['options']) <= $translator->maxStaticOptions()) {
            $serialized['options'] = array_map(static function (mixed $option) use ($translator): mixed {
                if (! is_array($option) || ! is_string($option['label'] ?? null)) {
                    return $option;
                }

                $label = $translator->translate('options', $option['label']);
                $value = $option['value'] ?? null;

                return [
                    ...$option,
                    'label' => $translator->subtitle('options', $label, $option['label'], is_string($value) ? $value : null),
                ];
            }, $serialized['options']);
        }

        return $serialized;
    }
}
