<?php

declare(strict_types=1);

use Adedsec\NovaTranslatedLabels\Tests\TestCase;
use Illuminate\Support\Facades\Lang;

uses(TestCase::class)->in('Feature');

/**
 * @param  array<string, string>  $lines  keys like "fields.Title"
 */
function translations(array $lines, string $locale = 'fa'): void
{
    $prefixed = [];

    foreach ($lines as $key => $value) {
        $prefixed['nova-labels.'.$key] = $value;
    }

    Lang::addLines($prefixed, $locale);
}
