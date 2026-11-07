<?php

declare(strict_types=1);

namespace Adedsec\NovaTranslatedLabels;

use Illuminate\Contracts\Config\Repository as Config;

final class Translator
{
    /** @var array<string, array<string, true>> */
    private array $missing = [];

    public function __construct(private readonly Config $config) {}

    /**
     * @param  'groups'|'fields'|'panels'|'filters'|'actions'|'options'  $kind
     */
    public function translate(string $kind, string $text, ?string $uriKey = null): string
    {
        if (trim($text) === '' || Label::isMarked($text) || $this->isTranslated($text)) {
            return $text;
        }

        $translated = ($uriKey === null ? null : $this->lookup(['overrides', $uriKey, $kind, $text]))
            ?? $this->lookup([$kind, $text]);

        if ($translated !== null) {
            return $translated;
        }

        // Option labels carrying digits are almost always data (codes, years, sizes), not wording.
        if ($kind !== 'options' || preg_match('/\d/', $text) !== 1) {
            $this->missing[$kind][$text] = true;
        }

        return $text;
    }

    /**
     * @param  'label'|'singular'  $key
     */
    public function resource(string $uriKey, string $key, string $fallback): string
    {
        $translated = $this->lookup(['resources', $uriKey, $key]);

        if ($translated !== null || $this->isTranslated($fallback)) {
            return $translated ?? $fallback;
        }

        $this->missing['resources'][$uriKey.'.'.$key.' = '.$fallback] = true;

        return $fallback;
    }

    public function isTranslated(string $text): bool
    {
        $pattern = $this->config->get('nova-translated-labels.translated_pattern');

        return is_string($pattern) && $pattern !== '' && preg_match($pattern, $text) === 1;
    }

    /**
     * Mark a label with what the config asks to show under it for this kind of label.
     *
     * @param  'groups'|'resources'|'fields'|'options'  $kind
     * @param  string|null  $original  the English text from the code, before translation
     * @param  string|null  $database  the table, column, foreign key or stored value behind it
     */
    public function subtitle(string $kind, string $label, ?string $original, ?string $database): string
    {
        $parts = match ($this->subtitleMode($kind)) {
            'database' => [$database],
            'original' => [$original],
            'both' => [$original, $database],
            default => [],
        };

        $parts = array_values(array_unique(array_filter(
            $parts,
            static fn (?string $part): bool => $part !== null && $part !== '' && $part !== $label,
        )));

        return Label::make($label, $parts === [] ? null : implode(' · ', $parts));
    }

    /**
     * @return 'database'|'original'|'both'|'none'
     */
    public function subtitleMode(string $kind): string
    {
        $mode = $this->config->get('nova-translated-labels.subtitles.'.$kind, 'database');

        return match (true) {
            $mode === true => 'database',
            in_array($mode, ['database', 'original', 'both'], true) => $mode,
            default => 'none',
        };
    }

    public function maxStaticOptions(): int
    {
        $max = $this->config->get('nova-translated-labels.max_static_options', 40);

        return is_numeric($max) ? (int) $max : 40;
    }

    /**
     * @return array<string, list<string>>
     */
    public function missing(): array
    {
        return array_map(
            static fn (array $texts): array => array_map(strval(...), array_keys($texts)),
            $this->missing,
        );
    }

    /**
     * @param  list<string>  $path
     */
    private function lookup(array $path): ?string
    {
        $file = $this->config->get('nova-translated-labels.file', 'nova-labels');
        $value = trans(is_string($file) ? $file : 'nova-labels');

        foreach ($path as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return is_string($value) && $value !== '' ? $value : null;
    }
}
