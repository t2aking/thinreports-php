<?php

use PHPUnit\Framework\TestCase;
use Smalot\PdfParser;

class FeatureTest extends TestCase
{
    public function analyzePDF($pdf_data): PDFAnalyzer
    {
        return PDFAnalyzer::analyze($pdf_data);
    }
}

class PDFAnalyzer
{
    /**
     * @throws Exception
     */
    public static function analyze($pdf_data): PDFAnalyzer
    {
        $parser = new PdfParser\Parser();
        return new self($parser->parseContent($pdf_data));
    }

    private $pdf;
    private $pages;
    private $properties;

    public function __construct($pdf)
    {
        $this->pdf = $pdf;

        $this->pages = $pdf->getPages();
        $this->properties = $pdf->getDetails();
    }

    public function getPropertyTitle()
    {
        return $this->properties['Title'];
    }

    public function getPropertyCreator()
    {
        return $this->properties['Creator'];
    }

    public function getSizeOfPage($page_number): array
    {
        $page_details = $this->pages[$page_number - 1]->getDetails();

        return array(
            'width' => $page_details['MediaBox'][2],
            'height' => $page_details['MediaBox'][3]
        );
    }

    public function getPageCount(): int
    {
        return count($this->pages);
    }

    public function getTextsInPage($page_number)
    {
        return $this->pages[$page_number - 1]->getText();
    }

    public function getFontsInPage($page_number): array
    {
        return array_map(
            static function ($font) {
                return preg_replace('/^[A-Z]+?\+/', '', $font->getName());
            },
            $this->pages[$page_number - 1]->getFonts()
        );
    }

    public function isEmptyPage($page_number): bool
    {
        $texts = str_replace(
            "\nPowered by TCPDF (www.tcpdf.org) ", '',
            $this->getTextsInPage($page_number)
        );
        return $texts === ' ' || $texts === '';
    }

    public function getImageContentsInPage($page_number): array
    {
        $images = array();

        foreach ($this->pages[$page_number - 1]->getXObjects() as $object) {
            if ($object instanceof PdfParser\XObject\Image) {
                $images[] = $object->getContent();
            }
        }
        return array_unique($images);
    }

    public function getImageCountInPage($page_number): int
    {
        return count($this->getImageContentsInPage($page_number));
    }
}
