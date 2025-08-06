<?php

use Thinreports\Exception\StandardException;

require_once __DIR__ . '/../test_helper.php';

class TextRenderingFeature extends FeatureTest
{
    public function test_staticTextsRenderingWithProperlyFont(): void
    {
        $report = new Thinreports\Report(__DIR__ . '/layouts/static_texts.tlf');
        try {
            $report->addPage();
        } catch (StandardException $e) {
            $this->fail('Failed to add a page to the report');
        }

        $this->assertRenderingTextAndFont($report);
    }

    public function test_dynamicTextRenderingWithProperlyFont(): void
    {
        $report = new Thinreports\Report(__DIR__ . '/layouts/dynamic_texts.tlf');
        try {
            $page = $report->addPage();
        } catch (StandardException $e) {
            $this->fail('Failed to add a page to the report');
        }

        try {
            $page->setItemValues(array(
                'helvetica' => 'Helvetica',
                'courier_new' => 'Courier New',
                'times_new_roman' => 'Times New Roman',
                'ipa_m' => 'IPA 明朝'
            ));
        } catch (StandardException $e) {
            $this->fail('Failed to set item values');
        }

        $this->assertRenderingTextAndFont($report);
    }

    private function assertRenderingTextAndFont($report): void
    {
        $analyzer = $this->analyzePDF($report->generate());

        $page_texts = $analyzer->getTextsInPage(1);
        $page_fonts = $analyzer->getFontsInPage(1);

        $expected_texts = array(
            'Helvetica',
            'Courier New',
            'Times New Roman',
            'IPA 明朝'
        );
        foreach ($expected_texts as $text) {
            $this->assertContains($text, $page_texts);
        }

        $expected_fonts = array(
            'Helvetica',
            'Courier',
            'Times-Roman',
            'IPAMincho'
        );
        foreach ($expected_fonts as $font) {
            $this->assertContains($font, $page_fonts);
        }
    }
}
