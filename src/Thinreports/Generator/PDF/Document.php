<?php

/*
 * This file is part of the Thinreports PHP package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thinreports\Generator\PDF;

use Com\Tecnick\Pdf\Tcpdf;
use Thinreports\Layout;

class Document
{
    /**
     * @var Tcpdf
     */
    private Tcpdf $pdf;

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
        $font_paths = [Font::getGeneratedPath()];
        if (defined('K_PATH_FONTS') && is_string(K_PATH_FONTS) && K_PATH_FONTS !== '') {
            $font_paths[] = K_PATH_FONTS;
        }
        $this->pdf = new Engine(unit: 'pt', subsetfont: true,
            fileOptions: ['allowedPaths' => $font_paths]);
        $this->pdf->font = new FontMetrics(kunit: 1, subset: true, fileHelper: $this->pdf->file);
        $this->pdf->setCreator('Thinreports Generator');
        $this->pdf->setDefaultCellPadding(0, 0, 0, 0);
        $this->pdf->setDefaultCellMargin(0, 0, 0, 0);

        if ($default_layout !== null) {
            $this->pdf->setTitle($default_layout->getReportTitle());
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
        $this->appendPage($page_format);

        $this->last_page_layout = $layout;
    }

    public function addBlankPage(): void
    {
        if ($this->last_page_layout !== null) {
            $page_format = $this->getRegisteredPageFormat($this->last_page_layout->getIdentifier());
        } else {
            $page_format = array('orientation' => 'P', 'size' => 'A4');
        }
        $this->appendPage($page_format);
    }

    /**
     * @return string PDF data
     */
    public function render(): string
    {
        if ($this->pdf->page->getPages() === []) {
            $this->addBlankPage();
        }
        return $this->pdf->getOutPDFString();
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
                'B4' => 'JIS_B4',
                'B5' => 'JIS_B5',
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

    private function appendPage(array $format): void
    {
        $data = ['orientation' => $format['orientation'], 'autobreak' => false,
            'margin' => ['PL' => 0, 'PR' => 0, 'PT' => 0, 'PB' => 0]];
        if (is_array($format['size'])) {
            $data['width'] = (float) $format['size'][0];
            $data['height'] = (float) $format['size'][1];
        } else {
            $data['format'] = $format['size'];
        }
        $this->pdf->addPage($data);
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
