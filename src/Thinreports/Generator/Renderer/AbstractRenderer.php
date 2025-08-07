<?php

/*
 * This file is part of the Thinreports PHP package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thinreports\Generator\Renderer;

use Thinreports\Generator\PDF;

/**
 * @access private
 */
abstract class AbstractRenderer
{
    /**
     * @var PDF\Document
     */
    protected PDF\Document $doc;

    /**
     * @param PDF\Document $doc
     */
    public function __construct(PDF\Document $doc)
    {
        $this->doc = $doc;
    }

    /**
     * @param array $styles
     * @return array
     */
    public function buildGraphicStyles(array $styles): array
    {
        return array(
            'stroke_color' => $styles['border-color'] ?? '',
            'stroke_width' => $styles['border-width'] ?? '',
            'stroke_dash'  => $styles['border-style'] ?? '',
            'fill_color'   => $styles['fill-color'] ?? ''
        );
    }

    /**
     * @param array $styles
     * @return array
     */
    public function buildTextStyles(array $styles): array
    {
        return array(
            'font_family'    => $styles['font-family'][0],
            'font_size'      => $styles['font-size'],
            'font_style'     => $styles['font-style'],
            'color'          => $styles['color'],
            'align'          => $styles['text-align'],
            'letter_spacing' => $this->buildLetterSpacing($styles['letter-spacing'])
        );
    }

    /**
     * @param string|null $valign
     * @return string
     */
    public function buildVerticalAlign(?string $valign): string
    {
        return $valign ?: 'top';
    }

    /**
     * @param string|null $letter_spacing
     * @return string|null
     */
    public function buildLetterSpacing(?string $letter_spacing): ?string
    {
        if (in_array($letter_spacing, array(null, 'auto', 'normal'), true)) {
            return null;
        }

        return $letter_spacing;
    }
}
