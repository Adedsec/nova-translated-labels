<?php

declare(strict_types=1);

namespace Adedsec\NovaTranslatedLabels\Console;

use Adedsec\NovaTranslatedLabels\Translator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource as NovaResource;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Throwable;

final class MissingTranslationsCommand extends Command
{
    private const FIELD_METHODS = ['fieldsForIndex', 'fieldsForDetail', 'fieldsForCreate', 'fieldsForUpdate', 'fieldsForInlineCreate', 'fieldsForPreview'];

    /** @var string */
    protected $signature = 'nova-translated-labels:missing
        {--path= : Directory holding your Nova resources (defaults to app/Nova)}
        {--user= : ID of the user to walk the resources as, for fields that check the current user}
        {--json : Print the missing labels as JSON, ready to paste into a lang file}';

    /** @var string */
    protected $description = 'List the Nova labels that have no translation for the current locale';

    public function handle(Translator $translator): int
    {
        $path = $this->option('path');
        $resources = $this->resourcesIn(is_string($path) && $path !== '' ? $path : app_path('Nova'));

        $request = NovaRequest::create('/nova-api', 'GET');
        $user = $this->user();
        $request->setUserResolver(static fn (): ?Authenticatable => $user);
        app()->instance(NovaRequest::class, $request);

        $failures = [];

        foreach ($resources as $resourceClass) {
            try {
                $this->walk($resourceClass, $request);
            } catch (Throwable $throwable) {
                $failures[] = [$resourceClass, $throwable->getMessage()];
            }
        }

        $missing = $translator->missing();

        if ($this->option('json')) {
            $this->line((string) json_encode($missing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } else {
            $rows = [];

            foreach ($missing as $kind => $texts) {
                foreach ($texts as $text) {
                    $rows[] = [$kind, $text];
                }
            }

            $this->table(['Kind', 'Label'], $rows);
        }

        if ($failures !== []) {
            $this->warn('Some resources could not be fully walked:');
            $this->table(['Resource', 'Error'], $failures);
        }

        $count = array_sum(array_map(count(...), $missing));
        $this->info($count.' labels have no translation.');

        return $count === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Nova's own resourcesIn() only resolves classes under app/, so read each file's namespace and class instead.
     *
     * @return list<class-string<NovaResource<Model>>>
     */
    private function resourcesIn(string $directory): array
    {
        $resources = [];

        foreach ((new Finder)->in($directory)->files()->name('*.php') as $file) {
            $source = (string) file_get_contents($file->getPathname());

            if (preg_match('/^namespace\s+([^;]+);/m', $source, $namespace) !== 1
                || preg_match('/^(?:final\s+|readonly\s+)*class\s+(\w+)/m', $source, $class) !== 1) {
                continue;
            }

            $resourceClass = $namespace[1].'\\'.$class[1];

            if (is_subclass_of($resourceClass, NovaResource::class) && ! (new ReflectionClass($resourceClass))->isAbstract()) {
                $resources[] = $resourceClass;
            }
        }

        sort($resources);

        return $resources;
    }

    private function user(): ?Authenticatable
    {
        $id = $this->option('user');

        if (! is_string($id) || $id === '') {
            return null;
        }

        $guard = config('nova.guard');

        return Auth::guard(is_string($guard) ? $guard : null)->getProvider()->retrieveById($id);
    }

    /**
     * @param  class-string<NovaResource<Model>>  $resourceClass
     */
    private function walk(string $resourceClass, NovaRequest $request): void
    {
        $resourceClass::group();
        $resourceClass::label();
        $resourceClass::singularLabel();

        $resource = new $resourceClass($resourceClass::newModel());

        foreach ($resource->buildAvailableFields($request, self::FIELD_METHODS) as $field) {
            $this->attempt(static fn (): mixed => $field->jsonSerialize());
        }

        foreach ($resource->resolveActions($request) as $action) {
            $this->attempt(static fn (): mixed => $action->jsonSerialize());
        }

        foreach ($resource->resolveFilters($request) as $filter) {
            $this->attempt(static fn (): mixed => $filter->jsonSerialize());
        }
    }

    /**
     * Serializing resolves option callbacks, which can need a real record or request state the walk cannot give.
     */
    private function attempt(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable) {
            // Skipped: the labels this would have reported are found when the page is really rendered.
        }
    }
}
