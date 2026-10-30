<?php
namespace Thinreports\Generator\PDF;

use PHPUnit\Framework\Attributes\DataProvider;
use Thinreports\TestCase;

class GraphicsTest extends TestCase
{
    private Engine $tcpdf;

    public function setup(): void
    {
        $this->tcpdf = new Engine(unit: 'pt');
        $this->tcpdf->addPage();
    }

    public function test_invisibleGraphicsEmitNoContent(): void
    {
        $graphics = new Graphics($this->tcpdf);
        $before = $this->tcpdf->page->getPage()['content'];
        $styles = ['stroke_width' => 0, 'stroke_color' => 'none', 'stroke_dash' => 'solid', 'fill_color' => 'none'];
        $graphics->drawLine(10, 20, 30, 40, $styles);
        $graphics->drawRect(10, 20, 30, 40, $styles);
        $graphics->drawEllipse(10, 20, 30, 40, $styles);
        $this->assertSame($before, $this->tcpdf->page->getPage()['content']);
    }

    public function test_drawBase64Image_reusesAndRemovesTemporaryFile(): void
    {
        $base64 = file_get_contents($this->dataDir() . '/image.png.base64');
        $graphics = new Graphics($this->tcpdf);
        try {
            $graphics->drawBase64Image($base64, 10, 20, 30, 40);
            $path = $graphics->getRegisteredImagePath(md5($base64));
            $graphics->drawBase64Image($base64, 10, 20, 30, 40);
            $this->assertSame($path, $graphics->getRegisteredImagePath(md5($base64)));
            $this->assertSame(base64_decode($base64), file_get_contents($path));
        } finally {
            $graphics->clearRegisteredImages();
        }
        $this->assertFileDoesNotExist($path);
    }

    #[DataProvider('graphicStyleProvider')]
    public function test_buildGraphicStyles($expected_result, $attrs): void
    {
        $test_graphics = new Graphics($this->tcpdf);

        $this->assertSame(
            $expected_result,
            $test_graphics->buildGraphicStyles($attrs)
        );
    }

    public static function graphicStyleProvider(): array
    {
        return array(
            array(
                array(
                    'stroke' => null,
                    'fill' => null
                ),
                array()
            ),
            array(
                array(
                    'stroke' => null,
                    'fill' => null
                ),
                array(
                    'fill_color' => 'none'
                )
            ),
            array(
                array(
                    'stroke' => array(
                        'width' => '1',
                        'color' => null,
                        'dash' => 0
                    ),
                    'fill' => array(0, 0, 0)
                ),
                array(
                    'stroke_width' => '1',
                    'stroke_color' => '',
                    'stroke_dash' => 'none',
                    'fill_color' => '#000000'
                )
            ),
            array(
                array(
                    'stroke' => array(
                        'width' => 1.5,
                        'color' => array(0, 0, 0),
                        'dash' => 0
                    ),
                    'fill' => null
                ),
                array(
                    'stroke_width' => 1.5,
                    'stroke_color' => 'black',
                    'stroke_dash' => 'solid'
                )
            ),
            array(
                array(
                    'stroke' => array(
                        'width' => 1.5,
                        'color' => array(0, 0, 0),
                        'dash' => '2,2'
                    ),
                    'fill' => null
                ),
                array(
                    'stroke_width' => 1.5,
                    'stroke_color' => 'black',
                    'stroke_dash' => 'dashed'
                )
            ),
            array(
                array(
                    'stroke' => array(
                        'width' => 1.5,
                        'color' => array(0, 0, 0),
                        'dash' => '1,2'
                    ),
                    'fill' => null
                ),
                array(
                    'stroke_width' => 1.5,
                    'stroke_color' => 'black',
                    'stroke_dash' => 'dotted'
                )
            )
        );
    }

    public function test_buildGraphicStylesRejectsUnsupportedBorderStyle(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported border style: "invalid"');

        (new Graphics($this->tcpdf))->buildGraphicStyles(array(
            'stroke_width' => 1,
            'stroke_color' => 'black',
            'stroke_dash' => 'invalid'
        ));
    }

    public function test_buildRenderingFlag(): void
    {
        $test_graphics = new Graphics($this->tcpdf);

        $this->assertEquals('DF', $test_graphics->buildRenderingFlag(array('width' => 1), []));
        $this->assertEquals('D',  $test_graphics->buildRenderingFlag(array('width' => 1), null));
        $this->assertEquals('F',  $test_graphics->buildRenderingFlag(null, []));
    }

    public function test_buildImagePosition(): void
    {
        $test_graphics = new Graphics($this->tcpdf);

        $this->assertEquals('LT', $test_graphics->buildImagePosition(array()));
        $this->assertEquals('CM', $test_graphics->buildImagePosition(array(
            'align' => 'center', 'valign' => 'middle'
        )));
    }

    public static function opaquePngProvider(): array
    {
        return [
            'RGB' => [false],
            'palette without tRNS' => [true],
        ];
    }

    #[DataProvider('opaquePngProvider')]
    public function test_drawImage_downsamplesOpaquePngWithoutMask(bool $palette): void
    {
        $png = $this->createPng(400, 300, $palette);
        // 50pt x 40pt at 300 dpi is 208 x 156 pixels, below the 400 x 300 source.
        $this->drawImageData($this->tcpdf, $png, 50, 40);

        $data = $this->imageData($this->tcpdf, 1);
        $this->assertFalse($data['splitalpha']);
        $this->assertArrayNotHasKey('mask', $data);
        $this->assertSame([208, 156], [$data['width'], $data['height']]);
        $this->assertStringNotContainsString('/SMask', $this->tcpdf->getOutPDFString());

        // The pixels are the ones tc-lib embeds when it resizes the image itself.
        $reference = new Engine(unit: 'pt');
        $reference->addPage();
        $iid = $reference->image->add('@' . $png, 208, 156);
        $expected = $this->imageData($reference, $iid);
        $this->assertTrue($expected['splitalpha']);
        $this->assertSamePixels($expected['plain']['raw'], $data['raw']);
    }

    public function test_drawImage_reusesDownsampledImage(): void
    {
        $png = $this->createPng(400, 300, false);
        $this->drawImageData($this->tcpdf, $png, 50, 40);
        $this->drawImageData($this->tcpdf, $png, 50, 40);

        $this->assertSame($this->imageData($this->tcpdf, 1)['key'], $this->imageData($this->tcpdf, 2)['key']);
    }

    public function test_drawImage_keepsAlphaOfTransparentPng(): void
    {
        $image = imagecreatetruecolor(400, 300);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 10, 20, 30, 64));
        ob_start();
        imagepng($image);
        $this->drawImageData($this->tcpdf, (string) ob_get_clean(), 50, 40);

        $this->assertArrayHasKey('mask', $this->imageData($this->tcpdf, 1));
        $this->assertStringContainsString('/SMask', $this->tcpdf->getOutPDFString());
    }

    public function test_drawImage_embedsSmallPngAsIs(): void
    {
        $png = $this->createPng(40, 30, false);
        $this->drawImageData($this->tcpdf, $png, 50, 40);

        $this->assertSame($png, $this->imageData($this->tcpdf, 1)['raw']);
    }

    private function createPng(int $width, int $height, bool $palette): string
    {
        $image = imagecreatetruecolor($width, $height);
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = (($x * 7 + $y * 3) % 256) << 16 | (($x * $y) % 256) << 8 | (($x ^ $y) % 256);
                imagesetpixel($image, $x, $y, $color);
            }
        }
        if ($palette) {
            imagetruecolortopalette($image, false, 255);
        }
        ob_start();
        imagepng($image);
        return (string) ob_get_clean();
    }

    private function drawImageData(Engine $pdf, string $png, float $width, float $height): void
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'thinreports-test');
        try {
            file_put_contents($path, $png);
            (new Graphics($pdf))->drawImage($path, 0, 0, $width, $height);
        } finally {
            unlink($path);
        }
    }

    private function imageData(Engine $pdf, int $iid): array
    {
        $images = (new \ReflectionProperty($pdf->image, 'image'))->getValue($pdf->image);
        return $pdf->image->getImageDataByKey($images[$iid]['key']);
    }

    private function assertSamePixels(string $expected_png, string $actual_png): void
    {
        $expected = imagecreatefromstring($expected_png);
        $actual = imagecreatefromstring($actual_png);
        $this->assertSame([imagesx($expected), imagesy($expected)], [imagesx($actual), imagesy($actual)]);
        $differences = 0;
        for ($y = 0; $y < imagesy($expected); $y++) {
            for ($x = 0; $x < imagesx($expected); $x++) {
                $rgb = fn ($image) => imagecolorsforindex($image, imagecolorat($image, $x, $y));
                [$a, $b] = [$rgb($expected), $rgb($actual)];
                $differences += (int) ([$a['red'], $a['green'], $a['blue']] !== [$b['red'], $b['green'], $b['blue']]);
            }
        }
        $this->assertSame(0, $differences);
    }
}
