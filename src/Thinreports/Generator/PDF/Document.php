<?php

/*
 * This file is part of the Thinreports PHP package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thinreports\Generator\PDF;

use TCPDF;
use Thinreports\Layout;

class Document
{
    /**
     * @var TCPDF
     */
    private TCPDF $pdf;

    /**
     * @var Graphics
     * @access public
     */
    public Graphics $graphics;

    /**
     * @var Text
     * @access public
     */
    public Text $text;

    /**
     * @var array
     */
    private array $page_formats = array();

    /**
     * @var Layout|null The layout that inserted at last.
     */
    private ?Layout $last_page_layout = null;

    /**
     * @param Layout|null $default_layout
     */
    public function __construct(?Layout $default_layout = null)
    {
        $this->pdf = new TCPDF('P', 'pt', 'A4', true, 'UTF-8');

        $this->pdf->SetCreator('Thinreports Generator');
        $this->pdf->SetAutoPageBreak(false);
        $this->pdf->SetMargins(0, 0, 0, true);
        $this->pdf->SetCellPadding(0);
        $this->pdf->SetCellMargins(0, 0, 0, 0);
        $this->pdf->SetPrintHeader(false);
        $this->pdf->SetPrintFooter(false);

        if ($default_layout !== null) {
            $this->pdf->SetTitle($default_layout->getReportTitle());
            $this->registerPageFormat($default_layout);
        }

        $this->initDrawer();
    }

    /**
     * @param Layout $layout
     */
    public function addPage(Layout $layout): void
    {
        $page_format = $this->registerPageFormat($layout);
        $this->pdf->AddPage($page_format['orientation'], $page_format['size']);

        $this->last_page_layout = $layout;
    }

    public function addBlankPage(): void
    {
        if ($this->last_page_layout !== null) {
            $page_format = $this->getRegisteredPageFormat($this->last_page_layout->getIdentifier());
        } else {
            $page_format = array('orientation' => 'P', 'size' => 'A4');
        }
        $this->pdf->AddPage($page_format['orientation'], $page_format['size']);
    }

    /**
     * @return string PDF data
     */
    public function render(): string
    {
        return $this->pdf->getPDFData();
    }

    /**
     * @param Layout $layout
     * @return array
     */
    public function buildPageFormat(Layout $layout): array
    {
        $orientation = $layout->isPortraitPage() ? 'P' : 'L';

        if ($layout->isUserPaperType()) {
            $size = $layout->getPageSize();
        } else {
            $size = match ($layout->getPagePaperType()) {
                'B4_ISO' => 'B4',
                'B5_ISO' => 'B5',
                'B4' => 'B4_JIS',
                'B5' => 'B5_JIS',
                default => $layout->getPagePaperType(),
            };
        }

        return array(
            'orientation' => $orientation,
            'size' => $size
        );
    }

    /**
     * @param Layout $layout
     * @return array
     */
    public function registerPageFormat(Layout $layout): array
    {
        $layout_identifier = $layout->getIdentifier();

        if (!array_key_exists($layout_identifier, $this->page_formats)) {
            $this->page_formats[$layout_identifier] = $this->buildPageFormat($layout);
        }
        return $this->getRegisteredPageFormat($layout_identifier);
    }

    /**
     * @param string $layout_identifier
     * @return array
     */
    public function getRegisteredPageFormat(string $layout_identifier): array
    {
        return $this->page_formats[$layout_identifier];
    }

    public function initDrawer(): void
    {
        $this->graphics = new Graphics($this->pdf);
        $this->text     = new Text($this->pdf);
    }

    public function __destruct()
    {
        $this->graphics->clearRegisteredImages();
    }
}
