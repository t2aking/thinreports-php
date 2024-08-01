<?php

use Thinreports\Exception\StandardException;

require_once __DIR__ . '/../test_helper.php';

class ImageRenderingFeature extends FeatureTest
{
    public function test_imageRendering(): void
    {
        $report = new Thinreports\Report(__DIR__ . '/layouts/images.tlf');
        $page = null;
        try {
            $page = $report->addPage();
        } catch (StandardException $e) {
            $this->fail($e->getMessage());
        }

        try {
            $page('image_jpeg')->setSource(__DIR__ . '/files/image-block-jpeg.jpg');
            $page('image_png')->setSource(__DIR__ . '/files/image-block-png.png');
        } catch (StandardException $e) {
            $this->fail($e->getMessage());
        }

        $analyzer = $this->analyzePDF($report->generate());

        $this->assertEquals(4, $analyzer->getImageCountInPage(1));
    }
}
