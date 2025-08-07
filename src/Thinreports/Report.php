<?php

/*
 * This file is part of the Thinreports PHP package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thinreports;

use Thinreports\Exception\StandardException;
use Thinreports\Page;
use Thinreports\Generator;

class Report
{
    private $default_layout;
    private $layouts = array();

    private $pages = array();
    private $page_count = 0;
    private $start_page_number = 1;

    /**
     * @param string|null $default_layout_filename
     */
    public function __construct(string $default_layout_filename = null)
    {
        if ($default_layout_filename !== null) {
            $this->default_layout = $this->buildLayout($default_layout_filename);
        }
    }

    /**
     * @param string|null $layout_filename
     * @param boolean $countable
     * @return Page\Page
     *
     * Usage example:
     *
     *  # Use default layout, count number of pages
     *  $page->addPage();
     *
     *  # Use other_layout.tlf, count number of pages
     *  $page->addPage('other_layout.tlf');
     *
     *  # Use default layout, don't count number of pages
     *  $page->addPage(null, false);
     * @throws StandardException
     */
    public function addPage(string $layout_filename = null, bool $countable = true): Page\Page
    {
        $layout = $this->loadLayout($layout_filename);
        $page_number = $this->getNextPageNumber($countable);

        $new_page = new Page\Page($this, $layout, $page_number, $countable);
        $this->pages[] = $new_page;

        return $new_page;
    }

    /**
     * @param bool $countable
     * @return Page\BlankPage
     */
    public function addBlankPage(bool $countable = true): Page\BlankPage
    {
        $page_number = $this->getNextPageNumber($countable);

        $blank_page = new Page\BlankPage($page_number, $countable);
        $this->pages[] = $blank_page;

        return $blank_page;
    }

    /**
     * @return int
     */
    public function getPageCount(): int
    {
        return $this->page_count;
    }

    /**
     * @return int
     */
    public function getLastPageNumber(): int
    {
        return ($this->start_page_number - 1) + $this->page_count;
    }

    /**
     * @param int $number
     */
    public function startPageNumberFrom(int $number): void
    {
        $this->start_page_number = $number;
    }

    /**
     * @return int
     */
    public function getStartPageNumber(): int
    {
        return $this->start_page_number;
    }

    /**
     * @return (Page\Page|Page\BlankPage)[]
     */
    public function getPages(): array
    {
        return $this->pages;
    }

    /**
     * @param string|null $filename
     *
     * @return boolean|string
     */
    public function generate(string $filename = null): bool|string
    {
        $pdf_data = Generator\PDFGenerator::generate($this);

        if ($filename === null) {
            return $pdf_data;
        }

        return file_put_contents($filename, $pdf_data) !== false;
    }

    /**
     * @access private
     *
     * @return Layout
     */
    public function getDefaultLayout(): Layout
    {
        return $this->default_layout;
    }

    /**
     * @access private
     *
     * @param bool $count
     * @return integer|null
     */
    private function getNextPageNumber(bool $count = true): ?int
    {
        if ($count) {
            $this->page_count ++;
            return ($this->start_page_number - 1) + $this->page_count;
        }

        return null;
    }

    /**
     * @access private
     *
     * @param string|null $layout_filename
     * @return Layout
     * @throws StandardException
     */
    public function loadLayout(string $layout_filename = null): Layout
    {
        if ($layout_filename !== null) {
            return $this->buildLayout($layout_filename);
        }

        if ($this->default_layout === null) {
            throw new Exception\StandardException('Layout Not Specified');
        }

        return $this->default_layout;
    }

    /**
     * @access private
     *
     * @param string $layout_filename
     * @return Layout
     * @throws StandardException
     */
    public function buildLayout(string $layout_filename): Layout
    {
        if (!array_key_exists($layout_filename, $this->layouts)) {
            $this->layouts[$layout_filename] = Layout::loadFile($layout_filename);
        }
        return $this->layouts[$layout_filename];
    }
}
