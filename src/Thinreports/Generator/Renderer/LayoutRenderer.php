<?php

/*
 * This file is part of the Thinreports PHP package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thinreports\Generator\Renderer;

use Thinreports\Exception\StandardException;
use Thinreports\Layout;
use Thinreports\Generator\PDF;
use Thinreports\Exception;

/**
 * @access private
 */
class LayoutRenderer extends AbstractRenderer
{
    private array $items;

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
    public function parse(Layout $layout): array
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

            if ($type === 'text') {
                foreach ($item['texts'] as $text) {
                    $text_lines[] = $text;
                }
                $attributes['content'] = implode("\n", $text_lines);
            }

            $items[] = $attributes;
        }
        return $items;
    }

    /**
     * @throws StandardException
     */
    public function render(): void
    {
        foreach ($this->items as $attributes) {
            $type_name = $attributes['type'];

            switch ($type_name) {
                case 'text':
                    $this->renderText($attributes);
                    break;
                case 'image':
                    $this->renderImage($attributes);
                    break;
                case 'rect':
                    $this->renderRect($attributes);
                    break;
                case 'ellipse':
                    $this->renderEllipse($attributes);
                    break;
                case 'line':
                    $this->renderLine($attributes);
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
    public function renderText(array $attrs): void
    {
        $styles = $this->buildTextStyles($attrs['style']);

        $valign = $attrs['style']['vertical-align'] ?? null;
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
     * @param array $attrs
     */
    public function renderRect(array $attrs): void
    {
        $styles = $this->buildGraphicStyles($attrs['style']);
        $styles['radius'] = $attrs['border-radius'] ?? 0;

        $this->doc->graphics->drawRect(
            $attrs['x'],
            $attrs['y'],
            $attrs['width'],
            $attrs['height'],
            $styles
        );
    }

    /**
     * @param array $attrs
     */
    public function renderEllipse(array $attrs): void
    {
        $this->doc->graphics->drawEllipse(
            $attrs['cx'],
            $attrs['cy'],
            $attrs['rx'],
            $attrs['ry'],
            $this->buildGraphicStyles($attrs['style'])
        );
    }

    /**
     * @param array $attrs
     */
    public function renderLine(array $attrs): void
    {
        $this->doc->graphics->drawLine(
            $attrs['x1'],
            $attrs['y1'],
            $attrs['x2'],
            $attrs['y2'],
            $this->buildGraphicStyles($attrs['style'])
        );
    }

    /**
     * @param array $attrs
     */
    public function renderImage(array $attrs): void
    {
        $this->doc->graphics->drawBase64Image(
            $attrs['data']['base64'],
            $attrs['x'],
            $attrs['y'],
            $attrs['width'],
            $attrs['height']
        );
    }
}
