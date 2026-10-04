<?php

/*
 * This file is part of the Thinreports PHP package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thinreports\Generator\PDF;

/**
 * @access private
 */
class Font
{
    public const STORE_PATH = '/../../../../fonts';

    /**
     * @var string[]
     */
    static public array $installed_builtin_fonts = array();

    static public array $builtin_unicode_fonts = array(
        'IPAMincho'  => 'ipam.ttf',
        'IPAPMincho' => 'ipamp.ttf',
        'IPAGothic'  => 'ipag.ttf',
        'IPAPGothic' => 'ipagp.ttf'
    );

    static public array $builtin_font_aliases = array(
        'Courier New'     => 'Courier',
        'Times New Roman' => 'Times'
    );

    public static function build(): void
    {
        foreach (array_keys(self::$builtin_unicode_fonts) as $name) {
            self::installBuiltinFont($name);
        }
    }

    public static function getGeneratedPath(): string
    {
        return dirname(__DIR__, 4) . '/fonts/generated';
    }

    public static function getDefinitionPath(string $family, string $style = ''): string
    {
        $name = strtolower(self::getFontName($family));
        $suffix = (str_contains($style, 'B') ? 'b' : '') . (str_contains($style, 'I') ? 'i' : '');
        foreach ([$name . $suffix, $name] as $key) {
            $path = self::getGeneratedPath() . '/' . $key . '.json';
            if (is_file($path)) {
                return $path;
            }
        }
        // Allow application-provided tc-lib font families (e.g. K_PATH_FONTS).
        return '';
    }

    /**
     * @param string $name
     * @return string
     */
    public static function getFontName(string $name): string
    {
        if (array_key_exists($name, self::$builtin_font_aliases)) {
            return self::$builtin_font_aliases[$name];
        }

        if (array_key_exists($name, self::$builtin_unicode_fonts)) {
            if (self::isInstalledFont($name)) {
                return static::$installed_builtin_fonts[$name];
            }

            return self::installBuiltinFont($name);
        }
        return $name;
    }

    /**
     * @param string $name
     * @return string
     */
    public static function installBuiltinFont(string $name): string
    {
        $filename = self::getBuiltinFontPath($name);

        $font_name = pathinfo($filename, PATHINFO_FILENAME);
        if (!is_file(self::getGeneratedPath() . '/' . $font_name . '.json')) {
            throw new \RuntimeException('Missing bundled font: ' . $font_name);
        }
        static::$installed_builtin_fonts[$name] = $font_name;

        return $font_name;
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isInstalledFont(string $name): bool
    {
        return array_key_exists($name, static::$installed_builtin_fonts);
    }

    /**
     * @param string $name
     * @return string
     */
    public static function getBuiltinFontPath(string $name): string
    {
        $font_directory = realpath(__DIR__ . self::STORE_PATH);
        return $font_directory . '/' . self::$builtin_unicode_fonts[$name];
    }

    /**
     * @param string $name
     * @return boolean
     */
    public static function isBuiltinUnicodeFont(string $name): bool
    {
        return array_key_exists($name, static::$builtin_unicode_fonts);
    }
}
