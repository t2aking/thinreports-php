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
}
