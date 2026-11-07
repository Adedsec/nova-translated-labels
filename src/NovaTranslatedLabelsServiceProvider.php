<?php

declare(strict_types=1);

namespace Adedsec\NovaTranslatedLabels;

use Adedsec\NovaTranslatedLabels\Console\MissingTranslationsCommand;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

final class NovaTranslatedLabelsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/nova-translated-labels.php', 'nova-translated-labels');

        // Scoped, so Octane workers start every request with an empty list of missing labels.
        $this->app->scoped(Translator::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/nova-translated-labels.php' => config_path('nova-translated-labels.php'),
            ], 'nova-translated-labels-config');

            $this->publishes([
                __DIR__.'/../stubs/nova-labels.php' => lang_path($this->app->getLocale().'/nova-labels.php'),
            ], 'nova-translated-labels-lang');

            $this->commands([MissingTranslationsCommand::class]);
        }

        Nova::serving(static function (ServingNova $event): void {
            if (config('nova-translated-labels.script', true)) {
                Nova::script('nova-translated-labels', __DIR__.'/../dist/nova-translated-labels.js');
            }
        });
    }
}
