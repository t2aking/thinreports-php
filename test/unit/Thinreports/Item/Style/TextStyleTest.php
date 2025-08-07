<?php
namespace Thinreports\Item\Style;

use ReflectionClass;
use Thinreports\Exception\UnavailableStyleValue;
use Thinreports\TestCase;
use Thinreports\Exception;

class TextStyleTest extends TestCase
{
    public function test_available_style_names(): void
    {
        $reflection = new ReflectionClass(TextStyle::class);
        $this->assertSame(
            array(
                'bold', 'italic', 'underline', 'linethrough',
                'align', 'valign', 'color', 'font_size'
            ),
            $reflection->getProperty('available_style_names')->getValue()
        );
    }

    public function test_set_color(): void
    {
        $test_style = new TextStyle(array('color' => 'none'));
        $test_style->set_color('#ff0000');

        $this->assertSame(array('color' => '#ff0000'), $test_style->export());
    }

    public function test_get_color(): void
    {
        $test_style = new TextStyle(array('color' => 'none'));

        $this->assertEquals('none', $test_style->get_color());
    }

    public function test_set_font_size(): void
    {
        $test_style = new TextStyle(array('font-size' => 1));
        $test_style->set_font_size(15.0);

        $this->assertSame(array('font-size' => 15.0), $test_style->export());
    }

    public function test_get_font_size(): void
    {
        $test_style = new TextStyle(array('font-size' => 18.0));

        $this->assertEquals(18.0, $test_style->get_font_size());
    }

    public function test_set_bold(): void
    {
        $test_style = new TextStyle(array('font-style' => array('italic')));
        $test_style->set_bold(true);

        $this->assertSame(array('font-style' => array('italic', 'bold')), $test_style->export());

        $test_style->set_bold(false);

        $this->assertSame(array('font-style' => array('italic')), $test_style->export());
    }

    public function test_get_bold(): void
    {
        $test_style = new TextStyle(array('font-style' => array('bold')));
        $this->assertTrue($test_style->get_bold());

        $test_style = new TextStyle(array('font-style' => array()));
        $this->assertFalse($test_style->get_bold());
    }

    public function test_set_italic(): void
    {
        $test_style = new TextStyle(array('font-style' => array()));
        $test_style->set_italic(true);

        $this->assertSame(array('font-style' => array('italic')), $test_style->export());

        $test_style->set_italic(false);

        $this->assertSame(array('font-style' => array()), $test_style->export());
    }

    public function test_get_italic(): void
    {
        $test_style = new TextStyle(array('font-style' => array('bold', 'italic')));
        $this->assertTrue($test_style->get_italic());

        $test_style = new TextStyle(array('font-style' => array('bold')));
        $this->assertFalse($test_style->get_italic());
    }

    public function test_set_underline(): void
    {
        $test_style = new TextStyle(array('font-style' => array('bold')));
        $test_style->set_underline(true);

        $this->assertSame(array('font-style' => array('bold', 'underline')), $test_style->export());

        $test_style->set_underline(false);

        $this->assertSame(array('font-style' => array('bold')), $test_style->export());
    }

    public function test_get_underline(): void
    {
        $test_style = new TextStyle(array('font-style' => array('underline')));
        $this->assertTrue($test_style->get_underline());

        $test_style = new TextStyle(array('font-style' => array()));
        $this->assertFalse($test_style->get_underline());
    }

    public function test_set_linethrough(): void
    {
        $test_style = new TextStyle(array('font-style' => array('bold')));
        $test_style->set_linethrough(true);

        $this->assertSame(array('font-style' => array('bold', 'linethrough')), $test_style->export());

        $test_style->set_linethrough(false);

        $this->assertSame(array('font-style' => array('bold')), $test_style->export());
    }

    public function test_get_linethrough(): void
    {
        $test_style = new TextStyle(array('font-style' => array('linethrough')));
        $this->assertTrue($test_style->get_linethrough());

        $test_style = new TextStyle(array('font-style' => array()));
        $this->assertFalse($test_style->get_linethrough());
    }

    public function test_set_align(): void
    {
        $test_style = new TextStyle(array('text-align' => ''));

        try {
            $test_style->set_align('unavailable_value');
            $this->fail();
        } catch (Exception\UnavailableStyleValue $e) {
            // OK
        }

        $test_style->set_align('right');

        $this->assertSame(array('text-align' => 'right'), $test_style->export());
    }

    public function test_get_align(): void
    {
        $test_style = new TextStyle(array('text-align' => ''));

        $this->assertEquals('left', $test_style->get_align());

        $test_style = new TextStyle(array('text-align' => 'right'));

        $this->assertEquals('right', $test_style->get_align());
    }

    /**
     * @throws UnavailableStyleValue
     */
    public function test_set_valign(): void
    {
        $test_style = new TextStyle(array('vertical-align' => ''));

        try {
            $test_style->set_valign('unavailable_value');
            $this->fail();
        } catch (Exception\UnavailableStyleValue $e) {
            // OK
        }

        $test_style->set_valign('top');

        $this->assertSame(array('vertical-align' => 'top'), $test_style->export());
    }

    public function test_get_valign(): void
    {
        $test_style = new TextStyle(array('vertical-align' => ''));

        $this->assertEquals('top', $test_style->get_valign());

        $test_style = new TextStyle(array('vertical-align' => 'bottom'));

        $this->assertEquals('bottom', $test_style->get_valign());
    }
}
