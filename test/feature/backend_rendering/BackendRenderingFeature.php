<?php

use PHPUnit\Framework\Attributes\DataProvider;
use Thinreports\Generator\PDF\Document;
use Thinreports\Generator\PDF\Font;

require_once __DIR__ . '/../test_helper.php';

class BackendRenderingFeature extends FeatureTest
{
    private function document(): Document
    {
        $doc = new Document();
        $doc->addBlankPage();
        return $doc;
    }

    private function styles(array $overrides = []): array
    {
        return array_replace(['font_family' => 'IPAGothic', 'font_size' => 16,
            'font_style' => [], 'color' => 'black'], $overrides);
    }

    private function page(Document $doc): \Smalot\PdfParser\Page
    {
        return (new \Smalot\PdfParser\Parser())->parseContent($doc->render())->getPages()[0];
    }

    public function test_japaneseWrapAndTruncateWithoutEllipsis(): void
    {
        $doc = $this->document();
        $doc->text->drawTextBox('あいうえおかきくけこさしすせそ', 20, 30, 80, 32, $this->styles());
        $runs = $this->page($doc)->getDataTm();
        $this->assertCount(2, $runs);
        $this->assertSame('あいうえお', $runs[0][1]);
        $this->assertSame('かきくけこ', $runs[1][1]);
        $this->assertEqualsWithDelta(20, $runs[0][0][4], 0.001);
        $this->assertEqualsWithDelta(16, $runs[0][0][5] - $runs[1][0][5], 0.001);
    }

    #[DataProvider('spacedWrappingProvider')]
    public function test_wrappingIncludesLetterSpacing(string $overflow, float $spacing, float $width, array $expected): void
    {
        $doc = $this->document();
        $doc->text->drawTextBox('あいうえおかきくけこ', 20, 30, $width, 32,
            $this->styles(['overflow' => $overflow, 'letter_spacing' => $spacing]));
        $runs = $this->page($doc)->getDataTm();
        $this->assertSame($expected, array_column($runs, 1));
        $this->assertEqualsWithDelta(16, $runs[0][0][5] - $runs[1][0][5], 0.001);
    }

    public static function spacedWrappingProvider(): array
    {
        return [
            'truncate positive spacing' => ['truncate', 2, 80, ['あいうえ', 'おかきく']],
            'expand positive spacing' => ['expand', 2, 80, ['あいうえ', 'おかきく', 'けこ']],
            'expand negative spacing' => ['expand', -2, 90, ['あいうえおか', 'きくけこ']],
        ];
    }

    #[DataProvider('paperSizeProvider')]
    public function test_jisAndIsoPaperSizes(string $paper, string $orientation, float $width, float $height): void
    {
        $layout = new \Thinreports\Layout(['items' => [], 'report' => [
            'paper-type' => $paper, 'orientation' => $orientation]], 'paper-size');
        $doc = new Document();
        $doc->addPage($layout);
        $doc->addBlankPage();
        $pdf = $this->analyzePDF($doc->render());
        $this->assertSame(2, $pdf->getPageCount());
        foreach ([1, 2] as $page) {
            $size = $pdf->getSizeOfPage($page);
            $this->assertEqualsWithDelta($width, $size['width'], 0.001);
            $this->assertEqualsWithDelta($height, $size['height'], 0.001);
        }
    }

    public static function paperSizeProvider(): array
    {
        return [
            ['B4', 'portrait', 728.504, 1031.811],
            ['B4', 'landscape', 1031.811, 728.504],
            ['B5', 'portrait', 515.906, 728.504],
            ['B5', 'landscape', 728.504, 515.906],
            ['B4_ISO', 'portrait', 708.661, 1000.630],
            ['B4_ISO', 'landscape', 1000.630, 708.661],
            ['B5_ISO', 'portrait', 498.898, 708.661],
            ['B5_ISO', 'landscape', 708.661, 498.898],
        ];
    }

    #[DataProvider('customSizeProvider')]
    public function test_customPaperAcceptsNumericStrings(string $orientation, float $width, float $height): void
    {
        $layout = new \Thinreports\Layout(['items' => [], 'report' => [
            'paper-type' => 'user', 'orientation' => $orientation,
            'width' => '400.5', 'height' => '600.25']], 'custom-size');
        $doc = new Document();
        $doc->addPage($layout);
        $size = $this->analyzePDF($doc->render())->getSizeOfPage(1);
        $this->assertEqualsWithDelta($width, $size['width'], 0.001);
        $this->assertEqualsWithDelta($height, $size['height'], 0.001);
    }

    public static function customSizeProvider(): array
    {
        return [['portrait', 400.5, 600.25], ['landscape', 600.25, 400.5]];
    }

    #[DataProvider('xbmSourceProvider')]
    public function test_xbmImageSourcesRender(bool $base64): void
    {
        $file = __DIR__ . '/files/pattern.xbm';
        $doc = $this->document();
        if ($base64) {
            $doc->graphics->drawBase64Image(base64_encode(file_get_contents($file)), 20, 30, 72, 72);
        } else {
            $doc->graphics->drawImage($file, 20, 30, 72, 72);
        }
        $images = array_values(array_filter($this->page($doc)->getXObjects(),
            static fn ($object) => $object instanceof \Smalot\PdfParser\XObject\Image));
        $this->assertNotEmpty($images);
        $this->assertEquals(8, $images[0]->get('Width')->getContent());
        $this->assertEquals(8, $images[0]->get('Height')->getContent());
        $this->assertNotEmpty($images[0]->getContent());
    }

    public static function xbmSourceProvider(): array
    {
        return ['file' => [false], 'base64' => [true]];
    }

    public function test_expandKeepsEveryLineAndDoesNotCreatePages(): void
    {
        $doc = $this->document();
        $doc->text->drawTextBox('あいうえおかきくけこさしすせそ', 20, 810, 80, 16,
            $this->styles(['overflow' => 'expand']));
        $pdf = (new \Smalot\PdfParser\Parser())->parseContent($doc->render());
        $this->assertCount(1, $pdf->getPages());
        $runs = $pdf->getPages()[0]->getDataTm();
        $this->assertCount(3, $runs);
        $this->assertSame('さしすせそ', $runs[2][1]);
    }

    public function test_fitRetainsTextAndRestoresFontSizeForNextBox(): void
    {
        $doc = $this->document();
        $text = 'あいうえおかきくけこさしすせそ';
        $doc->text->drawTextBox($text, 20, 30, 80, 24, $this->styles(['overflow' => 'fit']));
        $doc->text->drawTextBox('あいうえお', 20, 100, 80, 16, $this->styles());
        $page = $this->page($doc);
        $runs = $page->getDataTm();
        $last = array_pop($runs);
        $this->assertSame($text, implode('', array_column($runs, 1)));
        $this->assertSame('あいうえお', $last[1]);
        $content = $page->get('Contents')->getContent();
        preg_match_all('/\/F\d+ ([0-9.]+) Tf/', $content, $sizes);
        $this->assertLessThan(16, min(array_map('floatval', $sizes[1])));
        $this->assertEqualsWithDelta(16, (float) end($sizes[1]), 0.001);
    }

    #[DataProvider('alignmentProvider')]
    public function test_alignmentAndLineHeight(string $align, string $valign, float $x, float $baseline): void
    {
        $doc = $this->document();
        $doc->text->drawTextBox("あい\nうえ", 20, 30, 100, 80,
            $this->styles(['align' => $align, 'valign' => $valign, 'line_height' => 1.5]));
        $runs = $this->page($doc)->getDataTm();
        $this->assertCount(2, $runs);
        $this->assertEqualsWithDelta($x, $runs[0][0][4], 0.001);
        $this->assertEqualsWithDelta($baseline, 841.89 - $runs[0][0][5], 0.001);
        $this->assertEqualsWithDelta(24, $runs[0][0][5] - $runs[1][0][5], 0.001);
    }

    public static function alignmentProvider(): array
    {
        return [['left', 'top', 20, 48.08], ['center', 'middle', 54, 64.08], ['right', 'bottom', 88, 80.08]];
    }

    #[DataProvider('fontProvider')]
    public function test_bundledFontsNeedNoGlobalConfiguration(string $family): void
    {
        $doc = $this->document();
        $doc->text->drawTextBox('ABC 123', 20, 30, 150, 18,
            $this->styles(['font_family' => $family, 'font_size' => 18]));
        $this->assertStringContainsString('ABC 123', $this->page($doc)->getText());
        $this->assertFalse(defined('K_PATH_FONTS'));
    }

    public static function fontProvider(): array
    {
        return array_map(static fn ($family) => [$family],
            ['Helvetica', 'Courier New', 'Times New Roman', ...array_keys(Font::$builtin_unicode_fonts)]);
    }

    public function test_boldAndDecorationsDoNotLeakIntoNextBox(): void
    {
        $doc = $this->document();
        $doc->text->drawTextBox('太字', 20, 30, 100, 20,
            $this->styles(['font_style' => ['bold', 'underline', 'strikethrough']]));
        $doc->text->drawTextBox('通常', 20, 60, 100, 20, $this->styles());
        $page = $this->page($doc);
        $this->assertStringContainsString('太字', $page->getText());
        $this->assertStringContainsString('通常', $page->getText());
        $content = $page->get('Contents')->getContent();
        $this->assertMatchesRegularExpression('/2 Tr.*0 Tr/s', $content);
        $this->assertSame(1, substr_count($content, '2 Tr'));
    }

    public function test_fractionalImageBoxPreservesAspectAndAlignment(): void
    {
        $doc = $this->document();
        $file = __DIR__ . '/../image_rendering/files/image-block-png.png';
        [$iw, $ih] = getimagesize($file);
        $scale = min(120.4 / $iw, 105.9 / $ih);
        $w = $iw * $scale;
        $h = $ih * $scale;
        $doc->graphics->drawImage($file, 20.5, 30.25, 120.4, 105.9, ['align' => 'right', 'valign' => 'bottom']);
        $content = $this->page($doc)->get('Contents')->getContent();
        $this->assertMatchesRegularExpression('/([0-9.]+) 0(?:\.0+)? 0(?:\.0+)? ([0-9.]+) ([0-9.]+) ([0-9.]+) cm/', $content);
        preg_match('/([0-9.]+) 0(?:\.0+)? 0(?:\.0+)? ([0-9.]+) ([0-9.]+) ([0-9.]+) cm/', $content, $matrix);
        $this->assertEqualsWithDelta($w, (float) $matrix[1], 0.00001);
        $this->assertEqualsWithDelta($h, (float) $matrix[2], 0.00001);
        $this->assertEqualsWithDelta(20.5 + 120.4 - $w, (float) $matrix[3], 0.00001);
        $this->assertEqualsWithDelta(841.89 - 30.25 - 105.9, (float) $matrix[4], 0.00001);
    }

    public function test_largeImageIsDownsampledTo300Dpi(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'thinreports-large-image-');
        try {
            $image = imagecreatetruecolor(600, 300);
            imagefill($image, 0, 0, imagecolorallocate($image, 80, 120, 180));
            imagepng($image, $file);
            $doc = $this->document();
            $doc->graphics->drawImage($file, 20, 30, 72, 36);
            $objects = $this->page($doc)->getXObjects();
            $images = array_values(array_filter($objects,
                static fn ($object) => $object instanceof \Smalot\PdfParser\XObject\Image));
            $this->assertNotEmpty($images);
            // PNG output can include a separate alpha-mask image.
            foreach ($images as $image) {
                $this->assertEquals(300, $image->get('Width')->getContent());
                $this->assertEquals(150, $image->get('Height')->getContent());
            }
        } finally {
            unlink($file);
        }
    }

    public function test_standardFontStylesSelectRealFaces(): void
    {
        $doc = $this->document();
        foreach (['Helvetica', 'Courier', 'Times'] as $index => $family) {
            $doc->text->drawTextBox('ABC', 20, 30 + $index * 30, 150, 20,
                $this->styles(['font_family' => $family, 'font_style' => ['bold', 'italic']]));
        }
        $names = array_map(static fn ($font) => $font->getName(), $this->page($doc)->getFonts());
        foreach (['Helvetica-BoldOblique', 'Courier-BoldOblique', 'Times-BoldItalic'] as $name) {
            $this->assertContains($name, $names);
        }
    }

    public function test_roundedRectanglePreservesFillAndBorder(): void
    {
        $doc = $this->document();
        $doc->graphics->drawRect(20, 30, 100, 40, ['stroke_width' => 2, 'stroke_color' => 'red',
            'stroke_dash' => 'dotted', 'fill_color' => 'blue', 'radius' => 8]);
        $content = $this->page($doc)->get('Contents')->getContent();
        $this->assertStringContainsString('2.000000 w', $content);
        $this->assertStringContainsString('1.000000 0.000000 0.000000 RG', $content);
        $this->assertStringContainsString('0.000000 0.000000 1.000000 rg', $content);
        $this->assertStringContainsString('[1.000000 2.000000]', $content);
        $this->assertStringContainsString(' c', $content);
        $this->assertMatchesRegularExpression('/\s[bB]\s/', $content);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function test_customFontDirectoryWorksWithoutChangingBundledFonts(): void
    {
        $dir = sys_get_temp_dir() . '/thinreports-font-test-' . bin2hex(random_bytes(8));
        mkdir($dir, 0700);
        try {
            foreach (['ipam.json' => 'customipa.json', 'ipam.z' => 'ipam.z', 'ipam.ctg.z' => 'ipam.ctg.z'] as $source => $target) {
                copy(Font::getGeneratedPath() . '/' . $source, $dir . '/' . $target);
            }
            define('K_PATH_FONTS', $dir);
            $doc = $this->document();
            $doc->text->drawTextBox('独自フォント', 20, 30, 150, 20,
                $this->styles(['font_family' => 'customipa']));
            $doc->text->drawTextBox('標準フォント', 20, 60, 150, 20, $this->styles());
            $text = $this->page($doc)->getText();
            $this->assertStringContainsString('独自フォント', $text);
            $this->assertStringContainsString('標準フォント', $text);
            $this->assertSame($dir, K_PATH_FONTS);
        } finally {
            foreach (glob($dir . '/*') as $file) {
                unlink($file);
            }
            rmdir($dir);
        }
    }

    public function test_emptyReportProducesOneBlankPage(): void
    {
        $report = new Thinreports\Report();
        $pdf = $this->analyzePDF($report->generate());
        $this->assertSame(1, $pdf->getPageCount());
        $this->assertTrue($pdf->isEmptyPage(1));
    }
}
