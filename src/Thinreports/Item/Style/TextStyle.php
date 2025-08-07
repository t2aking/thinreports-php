<?php

/*
 * This file is part of the Thinreports PHP package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thinreports\Item\Style;

use Thinreports\Exception;
use Thinreports\Exception\UnavailableStyleValue;

/**
 * @access private
 */
class TextStyle extends BasicStyle
{
    static protected array $available_style_names = array(
        'bold', 'italic', 'underline', 'linethrough',
        'align', 'valign', 'color', 'font_size'
    );

    /**
     * @param string $color
     */
    public function set_color(string $color): void
    {
        $this->styles['color'] = $color;
    }

    /**
     * @return string
     */
    public function get_color(): string
    {
        return $this->readStyle('color');
    }

    /**
     * @param float|integer $size
     */
    public function set_font_size($size): void
    {
        $this->styles['font-size'] = $size;
    }

    /**
     * @return float|integer
     */
    public function get_font_size()
    {
        return $this->readStyle('font-size');
    }

    /**
     * @param bool $enable
     */
    public function set_bold(bool $enable): void
    {
        $this->updateFontStyle('bold', $enable);
    }

    /**
     * @return boolean
     */
    public function get_bold(): bool
    {
        return $this->hasFontStyle('bold');
    }

    /**
     * @param bool $enable
     */
    public function set_italic(bool $enable): void
    {
        $this->updateFontStyle('italic', $enable);
    }

    /**
     * @return bool
     */
    public function get_italic(): bool
    {
        return $this->hasFontStyle('italic');
    }

    /**
     * @param bool $enable
     */
    public function set_underline(bool $enable): void
    {
        $this->updateFontStyle('underline', $enable);
    }

    /**
     * @return bool
     */
    public function get_underline(): bool
    {
        return $this->hasFontStyle('underline');
    }

    /**
     * @param bool $enable
     */
    public function set_linethrough(bool $enable): void
    {
        $this->updateFontStyle('linethrough', $enable);
    }

    /**
     * @return bool
     */
    public function get_linethrough(): bool
    {
        return $this->hasFontStyle('linethrough');
    }

    /**
     * @param string $alignment
     */
    public function set_align($alignment)
    {
        $this->verifyStyleValue('align', $alignment, array('left', 'center', 'right'));
        $this->styles['text-align'] = $alignment;
    }

    /**
     * @return string
     */
    public function get_align()
    {
        $alignment = $this->readStyle('text-align');
        return $alignment === '' ? 'left' : $alignment;
    }

    /**
     * @param string $alignment
     * @throws UnavailableStyleValue
     */
    public function set_valign(string $alignment): void
    {
        $this->verifyStyleValue('valign', $alignment, array('top', 'middle', 'bottom'));
        $this->styles['vertical-align'] = $alignment;
    }

    /**
     * @return string
     */
    public function get_valign(): string
    {
        $alignment = $this->readStyle('vertical-align');
        return $alignment === '' ? 'top' : $alignment;
    }

    /**
     * @param string $type Availables are "bold", "italic", "underline", "linethrough"
     * @param bool $enable
     */
    private function updateFontStyle(string $type, bool $enable): void
    {
        if ($enable) {
            $this->enableFontStyle($type);
        } else {
            $this->disableFontStyle($type);
        }
    }

    /**
     * @param string $type {@see self::updateFontStyle()}
     */
    private function enableFontStyle(string $type): void
    {
        if (!$this->hasFontStyle($type)) {
            $this->styles['font-style'][] = $type;
        }
    }

    /**
     * @param string $type {@see self::updateFontStyle()}
     */
    private function disableFontStyle(string $type): void
    {
        if ($this->hasFontStyle($type)) {
            $index = array_search($type, $this->styles['font-style'], true);
            array_splice($this->styles['font-style'], $index, 1);
        }
    }

    /**
     * @param string $type {@see self::updateFontStyle()}
     * @return boolean
     */
    private function hasFontStyle(string $type): bool
    {
        return in_array($type, $this->styles['font-style'], true);
    }
}
