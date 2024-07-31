<?php
namespace Thinreports\Generator\PDF;

use Thinreports\TestCase;

class GraphicsTest extends TestCase
{
    private $tcpdf;

    public function setup(): void
    {
        $this->tcpdf = $this->getMockBuilder('TCPDF')
                            ->setMethods(array('Line', 'Rect', 'RoundedRect', 'Image', 'Ellipse'))
                            ->getMock();
    }

    public function test_drawLine(): void
    {
        $this->tcpdf->expects($this->once())
                    ->method('Line')
                    ->with(
                        100.0,
                        200.0,
                        300.0,
                        400.0,
                        array(
                            'width' => '1',
                            'color' => array(0, 0, 0),
                            'dash' => null
                        )
                    );

        $test_graphics = new Graphics($this->tcpdf);
        $test_graphics->drawLine(
            100.0,
            200.0,
            300.0,
            400.0,
            array(
                'stroke_width' => '1',
                'stroke_color' => '#000000',
                'stroke_dash' => 'none'
            )
        );

        $this->tcpdf->expects($this->never())
                    ->method('Line');

        $test_graphics->drawLine(
            100.0,
            200.0,
            300.0,
            400.0,
            array(
                'stroke_width' => '0',
                'stroke_color' => '#000000',
                'stroke_dash'  => 'none'
            )
        );
        $test_graphics->drawLine(
            100.0,
            200.0,
            300.0,
            400.0,
            array(
                'stroke_width' => '1',
                'stroke_color' => 'none',
                'stroke_dash'  => 'none'
            )
        );
    }

    public function test_drawRect(): void
    {
        $this->tcpdf->expects($this->once())
                    ->method('Rect')
                    ->with(
                        100.0,
                        200.0,
                        300.0,
                        400.0,
                        'DF',
                        array(
                            'all' => array(
                                'width' => '2',
                                'color' => array(255, 255, 255),
                                'dash' => '1,2'
                            )
                        ),
                        array(255, 0, 0)
                    );

        $test_graphics = new Graphics($this->tcpdf);
        $test_graphics->drawRect(
            100.0,
            200.0,
            300.0,
            400.0,
            array(
                'stroke_width' => '2',
                'stroke_color' => 'ffffff',
                'stroke_dash' => '1,2',
                'fill_color' => 'red'
            )
        );

        $this->tcpdf->expects($this->once())
                    ->method('RoundedRect')
                    ->with(
                        100.0,
                        200.0,
                        300.0,
                        400.0,
                        1,
                        '1111',
                        'DF',
                        array(
                            'width' => '2',
                            'color' => array(255, 255, 255),
                            'dash' => '1,2'
                        ),
                        array(255, 0, 0)
                    );

        $test_graphics->drawRect(
            100.0,
            200.0,
            300.0,
            400.0,
            array(
                'stroke_width' => '2',
                'stroke_color' => 'ffffff',
                'stroke_dash' => '1,2',
                'fill_color' => 'red',
                'radius' => 1
            )
        );
    }

    public function test_drawEllipse(): void
    {
        $this->tcpdf->expects($this->once())
                    ->method('Ellipse')
                    ->with(
                        100.0,
                        200.0,
                        300.0,
                        400.0,
                        0,
                        0,
                        360,
                        'DF',
                        array(
                            'width' => '3',
                            'color' => array(0, 0, 255),
                            'dash' => null
                        )
                    );

        $test_graphics = new Graphics($this->tcpdf);
        $test_graphics->drawEllipse(
            100.0,
            200.0,
            300.0,
            400.0,
            array(
                'stroke_width' => '3',
                'stroke_color' => 'blue',
                'stroke_dash' => 'none',
                'fill_color' => '0000ff'
            )
        );
    }

    public function test_drawImage(): void
    {
        $this->tcpdf->expects($this->once())
                    ->method('Image')
                    ->with(
                        '/path/to/image.png',
                        100.0,
                        200.0,
                        300.0,
                        400.0,
                        null,
                        null,
                        null,
                        true,
                        300,
                        null,
                        false,
                        false,
                        0,
                        'CM'
                    );

        $test_graphics = new Graphics($this->tcpdf);
        $test_graphics->drawImage(
            '/path/to/image.png',
            100.0,
            200.0,
            300.0,
            400.0,
            array(
                'align' => 'center',
                'valign' => 'middle'
            )
        );
    }

    public function test_drawBase64Image(): void
    {
        $base64_image = file_get_contents($this->dataDir() . '/image.png.base64');
        $base64_image_key = md5($base64_image);

        $test_graphics = new Graphics($this->tcpdf);

        $filename_expectation = function ($actual_filename)
            use (&$base64_image_key, &$test_graphics) {

            $expected_filename = $test_graphics->getRegisteredImagePath($base64_image_key);
            return $expected_filename === $actual_filename;
        };

        $this->tcpdf->expects($this->exactly(2))
                    ->method('Image')
                    ->with(
                        $this->callback($filename_expectation),
                        100.0, 200.0, 300.0, 400.0,
                        null, null, null, true, 300, null, false, false, 0, 'LT'
                    );

        $test_graphics->drawBase64Image(
            $base64_image, 100.0, 200.0, 300.0, 400.0
        );
        $test_graphics->drawBase64Image(
            $base64_image, 100.0, 200.0, 300.0, 400.0
        );

        $image_path = $test_graphics->getRegisteredImagePath($base64_image_key);

        $this->assertFileExists($image_path);
        $this->assertEquals(
            $base64_image,
            base64_encode(file_get_contents($image_path))
        );

        $test_graphics->clearRegisteredImages();
        $this->assertFileNotExists($image_path);
    }

    /**
     * @dataProvider graphicStyleProvider
     */
    public function test_buildGraphicStyles($expected_result, $attrs): void
    {
        $test_graphics = new Graphics($this->tcpdf);

        $this->assertSame(
            $expected_result,
            $test_graphics->buildGraphicStyles($attrs)
        );
    }
    public function graphicStyleProvider(): array
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
                        'dash' => '1,2'
                    ),
                    'fill' => null
                ),
                array(
                    'stroke_width' => 1.5,
                    'stroke_color' => 'black',
                    'stroke_dash' => '1,2'
                )
            )
        );
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
