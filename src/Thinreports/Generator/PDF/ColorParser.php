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
class ColorParser
{
    static private array $color_names = array(
        'red'     => 'ff0000',
        'yellow'  => 'fff000',
        'lime'    => '00ff00',
        'aqua'    => '00ffff',
        'blue'    => '0000ff',
        'fuchsia' => 'ff00ff',
        'maroon'  => '800000',
        'olive'   => '808000',
        'green'   => '008800',
        'teal'    => '008080',
        'navy'    => '000080',
        'purple'  => '800080',
        'black'   => '000000',
        'gray'    => '808080',
        'silver'  => 'c0c0c0',
        'white'   => 'ffffff'
    );

    /**
     * @param string|null $hex_or_name
     * @return string[]|null
     */
    public static function parse(?string $hex_or_name): ?array
    {
        if (empty($hex_or_name)) {
            return null;
        }

        if (array_key_exists($hex_or_name, self::$color_names)) {
            $hex_color = self::$color_names[$hex_or_name];
        } else {
            $hex_color = str_replace('#', '', $hex_or_name);
        }
        return self::hexToRgb($hex_color);
    }

    /**
     * @param string $hex_color
     * @return string[]
     */
    private static function hexToRgb(string $hex_color): array
    {
        $converter = static function ($hex) {
            return hexdec($hex);
        };
        return array_map($converter, str_split($hex_color, 2));
    }
}
