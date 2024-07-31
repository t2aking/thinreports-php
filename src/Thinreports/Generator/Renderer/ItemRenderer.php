<?php

/*
 * This file is part of the Thinreports PHP package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thinreports\Generator\Renderer;

use Thinreports\Item;

/**
 * @access private
 */
class ItemRenderer extends AbstractRenderer
{
    /**
     * @param Item\AbstractItem $item
     */
    public function render(Item\AbstractItem $item): void
    {
        if (!$this->isRenderable($item)) {
            return;
        }

        switch (true) {
            case $item instanceof Item\TextBlockItem:
                $this->renderTextBlockItem($item);
                break;
            case $item instanceof Item\ImageBlockItem:
                $this->renderImageBlockItem($item);
                break;
            case $item instanceof Item\PageNumberItem:
                $this->renderPageNumberItem($item);
                break;
            default:
                $this->renderBasicItem($item);
                break;
        }
    }

    /**
     * @param Item\AbstractItem $item
     * @return bool
     */
    public function isRenderable(Item\AbstractItem $item): bool
    {
        if (!$item->isVisible()) {
            return false;
        }

        switch (true) {
            case $item instanceof Item\TextBlockItem:
                return $item->hasReference() || $item->isPresent();
                break;
            case $item instanceof Item\ImageBlockItem:
                return $item->isPresent();
                break;
            case $item instanceof Item\PageNumberItem:
                $page = $item->getParent();
                return $page->isCountable() && $item->isForReport();
                break;
            default:
                return true;
                break;
        }
    }

    /**
     * @param Item\BasicItem $item
     */
    public function renderBasicItem(Item\BasicItem $item): void
    {
        switch (true) {
            case $item->isImage():
                $this->renderImageItem($item);
                break;
            case $item->isText():
                $this->renderTextItem($item);
                break;
            case $item->isRect():
                $this->renderRectItem($item);
                break;
            case $item->isEllipse():
                $this->renderEllipseItem($item);
                break;
            case $item->isLine():
                $this->renderLineItem($item);
                break;
        }
    }

    /**
     * @param Item\TextBlockItem $item
     */
    public function renderTextBlockItem(Item\TextBlockItem $item): void
    {
        $schema = $item->getSchema();
        $bounds = $item->getBounds();
        $styles = $this->buildTextStyles($item->exportStyles());

        $styles['valign'] = $schema['style']['vertical-align'];

        if ($schema['style']['overflow'] !== '') {
            $styles['overflow'] = $schema['style']['overflow'];
        }
        if ($schema['style']['line-height'] !== '') {
            $styles['line_height'] = $schema['style']['line-height'];
        }

        if ($item->isMultiple()) {
            $this->doc->text->drawTextBox(
                $item->getRealValue(),
                $bounds['x'],
                $bounds['y'],
                $bounds['width'],
                $bounds['height'],
                $styles
            );
        } else {
            $this->doc->text->drawText(
                $item->getRealValue(),
                $bounds['x'],
                $bounds['y'],
                $bounds['width'],
                $bounds['height'],
                $styles
            );
        }
    }

    /**
     * @param Item\ImageBlockItem $item
     */
    public function renderImageBlockItem(Item\ImageBlockItem $item): void
    {
        $bounds = $item->getBounds();
        $styles = $this->buildImageBoxItemStyles($item);

        $this->doc->graphics->drawImage(
            $item->getSource(),
            $bounds['x'],
            $bounds['y'],
            $bounds['width'],
            $bounds['height'],
            $styles
        );
    }

    /**
     * @param Item\PageNumberItem $item
     */
    public function renderPageNumberItem(Item\PageNumberItem $item): void
    {
        $bounds = $item->getBounds();

        $this->doc->text->drawText(
            $item->getFormattedPageNumber(),
            $bounds['x'],
            $bounds['y'],
            $bounds['width'],
            $bounds['height'],
            $this->buildTextStyles($item->exportStyles())
        );
    }

    /**
     * @param Item\BasicItem $item
     */
    public function renderImageItem(Item\BasicItem $item): void
    {
        $schema = $item->getSchema();
        $bounds = $item->getBounds();

        $this->doc->graphics->drawBase64Image(
            $schema['data']['base64'],
            $bounds['x'],
            $bounds['y'],
            $bounds['width'],
            $bounds['height']
        );
    }

    /**
     * @param Item\BasicItem $item
     */
    public function renderTextItem(Item\BasicItem $item): void
    {
        $schema = $item->getSchema();
        $bounds = $item->getBounds();

        $this->doc->text->drawTextBox(
            implode("\n", $schema['texts']),
            $bounds['x'],
            $bounds['y'],
            $bounds['width'],
            $bounds['height'],
            $this->buildTextStyles($schema)
        );
    }

    /**
     * @param Item\BasicItem $item
     */
    public function renderRectItem(Item\BasicItem $item): void
    {
//        $schema = $item->getSchema();
        $bounds = $item->getBounds();

//        $styles = $this->buildGraphicStyles($schema);
//        $styles['radius'] = $schema['border-radius'];

        $this->doc->graphics->drawRect(
            $bounds['x'],
            $bounds['y'],
            $bounds['width'],
            $bounds['height'],
            $this->buildGraphicStyles($item->exportStyles())
        );
    }

    /**
     * @param Item\BasicItem $item
     */
    public function renderEllipseItem(Item\BasicItem $item): void
    {
        $bounds = $item->getBounds();

        $this->doc->graphics->drawEllipse(
            $bounds['cx'],
            $bounds['cy'],
            $bounds['rx'],
            $bounds['ry'],
            $this->buildGraphicStyles($item->exportStyles())
        );
    }

    /**
     * @param Item\BasicItem $item
     */
    public function renderLineItem(Item\BasicItem $item): void
    {
        $bounds = $item->getBounds();

        $this->doc->graphics->drawLine(
            $bounds['x1'],
            $bounds['y1'],
            $bounds['x2'],
            $bounds['y2'],
            $this->buildGraphicStyles($item->exportStyles())
        );
    }

    /**
     * @param Item\ImageBlockItem $item
     * @return array
     */
    public function buildImageBoxItemStyles(Item\ImageBlockItem $item): array
    {
        $schema = $item->getSchema();

        $align  = $schema['style']['position-x'] ?: 'left';
        $valign = $schema['style']['position-y'] ?: 'top';

        if ($schema['style']['position-y'] === 'center') {
            $valign = 'middle';
        }
        return array(
            'align'  => $align,
            'valign' => $valign
        );
    }
}
