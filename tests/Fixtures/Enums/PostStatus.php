<?php

declare(strict_types=1);

namespace Adedsec\NovaTranslatedLabels\Tests\Fixtures\Enums;

enum PostStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
