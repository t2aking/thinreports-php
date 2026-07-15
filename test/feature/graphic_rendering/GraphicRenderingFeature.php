<?php

use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../test_helper.php';

class GraphicRenderingFeature extends FeatureTest
{
    #[DataProvider('graphicItemProvider')]
    public function test_graphicItemWithIdRendering(string $item_id, string $expected_pattern): void
    {
        $report = new Thinreports\Report(__DIR__ . '/layouts/graphics_with_ids.tlf');
        $page = $report->addPage();
        $page->item($item_id)->show();

        $analyzer = $this->analyzePDF($report->generate());

        $this->assertMatchesRegularExpression(
            $expected_pattern,
            $analyzer->getRawContentInPage(1)
        );
    }

    public function test_graphicItemWithIdRenderingUpdatedStyle(): void
    {
        $report = new Thinreports\Report(__DIR__ . '/layouts/graphics_with_ids.tlf');
        $page = $report->addPage();
        $page->item('line_with_id')->show()->setStyle('border_width', 2);

        $analyzer = $this->analyzePDF($report->generate());

        $this->assertMatchesRegularExpression(
            '/2\.000000 w .* RG\s+20\.000000 821\.890000 m/s',
            $analyzer->getRawContentInPage(1)
        );
    }

    public static function graphicItemProvider(): array
    {
        return array(
            'line' => array(
                'line_with_id',
                '/20\.000000 821\.890000 m\s+80\.000000 821\.890000 l\s+S/'
            ),
            'rect' => array(
                'rect_with_id',
                '/20\.000000 801\.890000 40\.000000 -20\.000000 re S/'
            ),
            'ellipse' => array(
                'ellipse_with_id',
                '/120\.000000 791\.890000 m\s+(?:[0-9.\s]+c\s+)+S/'
            )
        );
    }
}
