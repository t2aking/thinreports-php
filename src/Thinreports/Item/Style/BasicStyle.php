<?php

/*
 * This file is part of the Thinreports PHP package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thinreports\Item\Style;

use Thinreports\Exception;
use Thinreports\Exception\StandardException;

/**
 * @access private
 */
class BasicStyle
{
    static protected $available_style_names = array();
    protected $styles;

    /**
     * @param array $item_styles
     */
    public function __construct(array $item_styles)
    {
        $this->styles = $item_styles;
    }

    /**
     * @param string $style_name
     * @param mixed $value
     * @throws StandardException
     */
    public function set(string $style_name, $value): void
    {
        $this->verifyStyleName($style_name);

        $setter = "set_{$style_name}";
        $this->$setter($value);
    }

    /**
     * @param string $style_name
     * @return mixed $value
     * @throws StandardException
     */
    public function get(string $style_name)
    {
        $this->verifyStyleName($style_name);

        $getter = "get_{$style_name}";
        return $this->$getter();
    }

    /**
     * @return array
     */
    public function export(): array
    {
        return $this->styles;
    }

    /**
     * @param string $raw_style_name
     * @return mixed
     */
    public function readStyle(string $raw_style_name)
    {
        if (array_key_exists($raw_style_name, $this->styles)) {
            return $this->styles[$raw_style_name];
        }

        if (array_key_exists('style', $this->styles) && array_key_exists($raw_style_name, $this->styles['style'])) {
            return $this->styles['style'][$raw_style_name];
        }

        return null;
    }

    /**
     * @param string $style_name
     * @throws Exception\StandardException
     */
    public function verifyStyleName(string $style_name): void
    {
        if (!in_array($style_name, static::$available_style_names, true)) {
            throw new Exception\StandardException('Unavailable Style Name', $style_name);
        }
    }

    /**
     * @param string $style_name
     * @param mixed $value
     * @param array $allows
     * @throws Exception\UnavailableStyleValue
     */
    public function verifyStyleValue(string $style_name, $value, array $allows): void
    {
        if (!in_array($value, $allows, true)) {
            throw new Exception\UnavailableStyleValue($style_name, $value, $allows);
        }
    }
}
