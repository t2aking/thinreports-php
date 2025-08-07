<?php

/*
 * This file is part of the Thinreports PHP package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thinreports\Generator;

use Thinreports\Exception\StandardException;
use Thinreports\Item\AbstractItem;
use Thinreports\Report;
use Thinreports\Layout;
use Thinreports\Page\Page;

/**
 * @access private
 */
class PDFGenerator
{
    /**
     * @var Report
     */
    private Report $report;

    /**
     * @var Renderer\LayoutRenderer[]
     */
    private array $layout_renderers = array();

    /**
     * @var Renderer\ItemRenderer
     */
    private Renderer\ItemRenderer $item_renderer;

    /**
     * @var PDF\Document
     */
    private PDF\Document $doc;

    /**
     * @param Report $report
     * @return string
     * @throws StandardException
     */
    public static function generate(Report $report): string
    {
        return (new self($report))->render();
    }

    /**
     * @param Report $report
     */
    public function __construct(Report $report)
    {
        $this->report = $report;
        $this->doc = new PDF\Document($report->getDefaultLayout());
        $this->item_renderer = new Renderer\ItemRenderer($this->doc);
    }

    /**
     * @return string
     * @throws StandardException
     */
    public function render(): string
    {
        foreach ($this->report->getPages() as $page) {
            if ($page->isBlank()) {
                $this->doc->addBlankPage();
            } else {
                $this->renderPage($page);
            }
        }
        return $this->doc->render();
    }

    /**
     * @param Page $page
     * @throws StandardException
     */
    public function renderPage(Page $page): void
    {
        $layout = $page->getLayout();

        $this->doc->addPage($layout);

        $this->renderLayout($layout);
        $this->renderItems($page->getFinalizedItems());
    }

    /**
     * @param Layout $layout
     * @throws StandardException
     */
    public function renderLayout(Layout $layout): void
    {
        $layout_identifier = $layout->getIdentifier();

        if (array_key_exists($layout_identifier, $this->layout_renderers)) {
            $renderer = $this->layout_renderers[$layout_identifier];
        } else {
            $renderer = new Renderer\LayoutRenderer($this->doc, $layout);
            $this->layout_renderers[$layout_identifier] = $renderer;
        }
        $renderer->render();
    }

    /**
     * @param AbstractItem[] $items
     */
    public function renderItems(array $items): void
    {
        foreach ($items as $item) {
            $this->item_renderer->render($item);
        }
    }
}
