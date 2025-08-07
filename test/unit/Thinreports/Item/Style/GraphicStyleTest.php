<?php
namespace Thinreports\Item\Style;

use ReflectionClass;
use Thinreports\TestCase;

class GraphicStyleTest extends TestCase
{
    public function test_available_style_names(): void
    {
        $reflection = new ReflectionClass(GraphicStyle::class);
        $this->assertSame(
            array('border_color', 'border_width', 'border', 'fill_color'),
            $reflection->getProperty('available_style_names')->getValue()
        );
    }

    public function test_set_border_width(): void
    {
        $test_style = new GraphicStyle(array('border-width' => 1));
        $test_style->set_border_width(999.9);

        $this->assertSame(array('border-width' => 999.9), $test_style->export());
    }

    public function test_get_border_width(): void
    {
        $test_style = new GraphicStyle(array('border-width' => 999));

        $this->assertEquals(999, $test_style->get_border_width());
    }

    public function test_set_border_color(): void
    {
        $test_style = new GraphicStyle(array('border-color' => 'none'));
        $test_style->set_border_color('#000000');

        $this->assertSame(array('border-color' => '#000000'), $test_style->export());
    }

    public function test_get_border_color(): void
    {
        $test_style = new GraphicStyle(array('border-color' => 'red'));

        $this->assertEquals('red', $test_style->get_border_color());
    }

    public function test_set_border(): void
    {
        $test_style = new GraphicStyle(array('border-color' => 'none', 'border-width' => 1));
        $test_style->set_border(array(9, '#ffffff'));

        $this->assertSame(array('border-color' => '#ffffff', 'border-width' => 9), $test_style->export());
    }

    public function test_get_border(): void
    {
        $test_style = new GraphicStyle(array('border-color' => 'none', 'border-width' => 1.0));

        $this->assertEquals(array(1.0, 'none'), $test_style->get_border());
    }

    public function test_set_fill_color(): void
    {
        $test_style = new GraphicStyle(array('fill-color' => 'none'));
        $test_style->set_fill_color('#000000');

        $this->assertSame(array('fill-color' => '#000000'), $test_style->export());
    }

    public function test_get_fill_color(): void
    {
        $test_style = new GraphicStyle(array('fill-color' => 'blue'));

        $this->assertEquals('blue', $test_style->get_fill_color());
    }
}
