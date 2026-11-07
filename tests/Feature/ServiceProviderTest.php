<?php

declare(strict_types=1);

use Adedsec\NovaTranslatedLabels\NovaTranslatedLabelsServiceProvider;
use Illuminate\Support\ServiceProvider;

it('publishes the lang file under the name set in the config', function (): void {
    config(['nova-translated-labels.file' => 'admin']);

    (new NovaTranslatedLabelsServiceProvider(app()))->boot();

    expect(ServiceProvider::pathsToPublish(NovaTranslatedLabelsServiceProvider::class, 'nova-translated-labels-lang'))
        ->toContain(lang_path('fa/admin.php'));
});
