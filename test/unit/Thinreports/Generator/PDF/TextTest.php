<?php
namespace Thinreports\Generator\PDF;

use PHPUnit\Framework\Attributes\DataProvider;
use Thinreports\TestCase;

class TextTest extends TestCase
{
    private Engine $tcpdf;

    public function setup(): void
    {
        $this->tcpdf = new Engine(unit: 'pt', fileOptions: ['allowedPaths' => [Font::getGeneratedPath()]]);
        $this->tcpdf->font = new FontMetrics(kunit: 1, fileHelper: $this->tcpdf->file);
        $this->tcpdf->setDefaultCellPadding(0, 0, 0, 0);
        $this->tcpdf->addPage();
    }

    public function test_drawTextBox_with_color_none(): void
    {
        $before = $this->tcpdf->page->getPage()['content'];
        (new Text($this->tcpdf))->drawTextBox('hidden', 10, 20, 100, 30, [
            'font_style' => [], 'font_size' => 18, 'font_family' => 'Helvetica', 'color' => 'none',
        ]);
        $this->assertSame($before, $this->tcpdf->page->getPage()['content']);
    }

    public function test_drawText_flattensNewlines(): void
    {
        (new Text($this->tcpdf))->drawText("row1\nrow2\nrow3", 10, 20, 300, 30, [
            'font_style' => [], 'font_size' => 18, 'font_family' => 'Helvetica', 'color' => 'black',
        ]);
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseContent($this->tcpdf->getOutPDFString());
        $this->assertStringContainsString('row1 row2 row3', $pdf->getText());
    }

    #[DataProvider('boxAttributesProvider')]
    public function test_buildTextBoxStyles($expected_styles, $box_attrs): void
    {
        $test_text = new Text($this->tcpdf);
        $this->assertEquals(
            $expected_styles,
            $test_text->buildTextBoxStyles(100, $box_attrs)
        );
    }
    public static function boxAttributesProvider(): array
    {
        $correct_text_attrs = array(
            'result' => array(
                'font_size' => '18',
                'font_family' => 'Helvetica',
                'font_style' => '',
                'color' => null,
                'align' => 'L',
                'valign' => 'T',
                'line_height' => 1,
                'letter_spacing' => 0
            ),
            'attrs' => array(
                'font_style' => array(),
                'font_size' => '18',
                'font_family' => 'Helvetica',
                'color' => 'none'
            )
        );

        $case_single_row_is_true = array(
            array_merge($correct_text_attrs['result'], array(
                'line_height' => 1,
                'overflow' => array(
                    'fit_cell' => false,
                    'max_height' => 100
                )
            )),
            array_merge($correct_text_attrs['attrs'], array(
                'single_row' => true,
                'line_height' => '20',
                'overflow' => 'truncate',
            ))
        );

        $case_single_row_is_omitted = array(
            array_merge($correct_text_attrs['result'], array(
                'line_height' => '999',
                'overflow' => array(
                    'fit_cell' => false,
                    'max_height' => 100
                )
            )),
            array_merge($correct_text_attrs['attrs'], array(
                'line_height' => '999',
                'overflow' => 'truncate'
            ))
        );

        $case_single_row_is_false = array(
            array_merge($correct_text_attrs['result'], array(
                'line_height' => '999',
                'overflow' => array(
                    'fit_cell' => false,
                    'max_height' => 100
                )
            )),
            array_merge($correct_text_attrs['attrs'], array(
                'single_row' => false,
                'line_height' => '999',
                'overflow' => 'truncate'
            ))
        );

        $case_overflow_is_omitted = array(
            array_merge($correct_text_attrs['result'], array(
                'overflow' => array(
                    'fit_cell' => false,
                    'max_height' => 100
                )
            )),
            array_merge($correct_text_attrs['attrs'], array(
                'single_row' => true,
                'line_height' => '20'
            ))
        );

        $case_overflow_is_fit = array(
            array_merge($correct_text_attrs['result'], array(
                'overflow' => array(
                    'fit_cell' => true,
                    'max_height' => 100
                )
            )),
            array_merge($correct_text_attrs['attrs'], array(
                'overflow' => 'fit',
                'single_row' => true,
                'line_height' => '20'
            ))
        );

        $case_overflow_is_expand = array(
            array_merge($correct_text_attrs['result'], array(
                'overflow' => array(
                    'fit_cell' => false,
                    'max_height' => 0
                )
            )),
            array_merge($correct_text_attrs['attrs'], array(
                'overflow' => 'expand',
                'single_row' => true,
                'line_height' => '20'
            ))
        );

        return array(
            $case_single_row_is_true,
            $case_single_row_is_omitted,
            $case_single_row_is_false,
            $case_overflow_is_omitted,
            $case_overflow_is_fit,
            $case_overflow_is_expand
        );
    }

    #[DataProvider('textAttributesProvider')]
    public function test_buildTextStyles($expected_styles, $text_attrs): void
    {
        $test_text = new Text($this->tcpdf);
        $this->assertSame($expected_styles, $test_text->buildTextStyles($text_attrs));
    }
    public static function textAttributesProvider(): array
    {
        $case1 = array(
            array(
                'font_size' => '18',
                'font_family' => 'Helvetica',
                'font_style' => '',
                'color' => null,
                'align' => 'L',
                'valign' => 'T',
                'line_height' => 1,
                'letter_spacing' => 0
            ),
            array(
                'font_style' => array(),
                'font_size' => '18',
                'font_family' => 'Helvetica',
                'color' => ''
            )
        );

        $case2 = array(
            array(
                'font_size' => '18',
                'font_family' => 'Helvetica',
                'font_style' => 'BIUD',
                'color' => array(0, 0, 0),
                'align' => 'R',
                'valign' => 'B',
                'line_height' => 1.5,
                'letter_spacing' => 10
            ),
            array(
                'font_style' => array('bold', 'italic', 'underline', 'strikethrough'),
                'font_size' => '18',
                'font_family' => 'Helvetica',
                'color' => '#000000',
                'align' => 'right',
                'valign' => 'bottom',
                'line_height' => 1.5,
                'letter_spacing' => 10
            )
        );

        return array($case1, $case2);
    }
}
