<?php

declare(strict_types=1);

namespace Adedsec\NovaTranslatedLabels;

final class Label
{
    /**
     * U+2063 INVISIBLE SEPARATOR wraps the real name so the script can find it.
     */
    public const MARKER = "\u{2063}";

    public static function make(string $label, ?string $realName): string
    {
        if ($realName === null || $realName === '' || $realName === $label || self::isMarked($label)) {
            return $label;
        }

        return $label.self::MARKER.' ('.$realName.')'.self::MARKER;
    }

    public static function strip(string $label): string
    {
        return (string) preg_replace('/\x{2063}[^\x{2063}]*\x{2063}/u', '', $label);
    }

    public static function isMarked(string $label): bool
    {
        return str_contains($label, self::MARKER);
    }
}
