<?php

/*
 * This file is part of the Thinreports PHP package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thinreports\Generator\Renderer;

use Thinreports\Layout;
use Thinreports\Generator\PDF;
use Thinreports\Exception;

/**
 * @access private
 */
class LayoutRenderer extends AbstractRenderer
{
    private $items = array();

    /**
     * @param PDF\Document $doc
     * @param Layout $layout
     */
    public function __construct(PDF\Document $doc, Layout $layout)
    {
        parent::__construct($doc);
        $this->items = $this->parse($layout);
    }

    /**
     * @param Layout $layout
     * @return array()
     */
    public function parse(Layout $layout)
    {
        $items = array();
        $text_lines = array();
        $schema = $layout->getSchema();

        foreach ($schema['items'] as $item) {
            $attributes = $item;
            $type = $item['type'];

            if (!($type === 'text' || $type === 'image' || $type === 'rect' || $type === 'ellipse' || $type === 'line')) {
                continue;
            }

            switch ($type) {
                case 'text':
                    foreach ($item['texts'] as $text) {
                        $text_lines[] = $text;
                    }
                    $attributes['content'] = implode("\n", $text_lines);
                    break;
                case 'image':
                    $attributes['xlink:href'] = $item['data']['base64'];
                    break;
            }

            $items[] = $attributes;
        }
        return $items;
    }

    public function render()
    {
        foreach ($this->items as $attributes) {
            $type_name = $attributes['type'];

            switch ($type_name) {
                case 'text':
                    $this->renderText($attributes);
                    break;
                case 'image':
                    $this->renderSVGImage($attributes);
                    break;
                case 'rect':
                    $this->renderSVGRect($attributes);
                    break;
                case 'ellipse':
                    $this->renderSVGEllipse($attributes);
                    break;
                case 'line':
                    $this->renderSVGLine($attributes);
                    break;
                default:
                    throw new Exception\StandardException('Unknown Element', $type_name);
                    break;
            }
        }
    }

    /**
     * @param array $attrs
     */
    public function renderText(array $attrs)
    {
        $styles = $this->buildTextStyles($attrs['style']);

        if (array_key_exists('vertical-align', $attrs['style'])) {
            $valign = $attrs['style']['vertical-align'];
        } else {
            $valign = null;
        }
        $styles['valign'] = $this->buildVerticalAlign($valign);

        if (array_key_exists('line-height', $attrs['style'])
                && $attrs['style']['line-height'] !== '') {
            $styles['line_height'] = $attrs['style']['line-height'];
        }

        $this->doc->text->drawTextBox(
            $attrs['texts'][0],
            $attrs['x'],
            $attrs['y'],
            $attrs['width'],
            $attrs['height'],
            $styles
        );
    }

    /**
     * @param array $svg_attrs
     */
    public function renderSVGRect(array $svg_attrs)
    {
        $styles = $this->buildGraphicStyles($svg_attrs);
        $styles['radius'] = $svg_attrs['rx'];

        $this->doc->graphics->drawRect(
            $svg_attrs['x'],
            $svg_attrs['y'],
            $svg_attrs['width'],
            $svg_attrs['height'],
            $styles
        );
    }

    /**
     * @param array $svg_attrs
     */
    public function renderSVGEllipse(array $svg_attrs)
    {
        $this->doc->graphics->drawEllipse(
            $svg_attrs['cx'],
            $svg_attrs['cy'],
            $svg_attrs['rx'],
            $svg_attrs['ry'],
            $this->buildGraphicStyles($svg_attrs)
        );
    }

    /**
     * @param array $svg_attrs
     */
    public function renderSVGLine(array $svg_attrs)
    {
        $this->doc->graphics->drawLine(
            $svg_attrs['x1'],
            $svg_attrs['y1'],
            $svg_attrs['x2'],
            $svg_attrs['y2'],
            $this->buildGraphicStyles($svg_attrs)
        );
    }

    /**
     * @param array $svg_attrs
     */
    public function renderSVGImage(array $svg_attrs)
    {
        $this->doc->graphics->drawBase64Image(
            $this->extractBase64Data($svg_attrs),
            $svg_attrs['x'],
            $svg_attrs['y'],
            $svg_attrs['width'],
            $svg_attrs['height']
        );
    }
}
