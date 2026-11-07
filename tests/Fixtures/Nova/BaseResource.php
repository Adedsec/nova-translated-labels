<?php

declare(strict_types=1);

namespace Adedsec\NovaTranslatedLabels\Tests\Fixtures\Nova;

use Adedsec\NovaTranslatedLabels\Concerns\HasTranslatedLabels;
use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Resource as NovaResource;

/**
 * @template TModel of Model
 *
 * @extends NovaResource<TModel>
 */
abstract class BaseResource extends NovaResource
{
    use HasTranslatedLabels;
}
