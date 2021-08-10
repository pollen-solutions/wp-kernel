<?php

declare(strict_types=1);

namespace Pollen\WpKernel\Support;

class Locale
{
    /**
     * Locale identifier.
     * @var string|null
     */
    protected static ?string $locale = null;

    /**
     * List of available languages parameters.
     * @var array[]
     */
    protected static array $languages = [];

    /**
     * Gets the locale identifier.
     *
     * @return string|null
     */
    public static function get(): ?string
    {
        return self::$locale;
    }

    /**
     * Sets the locale identifier.
     *
     * @param string $locale
     *
     * @return void
     */
    public static function set(string $locale): void
    {
        self::$locale = $locale;
    }

    /**
     * Get parameters for an available language.
     *
     * @param string|null $locale
     *
     * @return array|null
     */
    public static function getLanguage(string $locale = null): ?array
    {
        return self::$languages[$locale??self::$locale] ?? null;
    }

    /**
     * Set the list of available languages parameters.
     *
     * @param array<string, array> $languages.
     *
     * @return void
     */
    public static function setLanguages(array $languages): void
    {
        self::$languages = $languages;
    }
}