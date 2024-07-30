<?php
namespace Thinreports\Item;

use Thinreports\TestCase;
use Thinreports\Report;
use Thinreports\Page\Page;
use Thinreports\Item\Style\BasicStyle;
use Thinreports\Item\Style\TextStyle;
use Thinreports\Item\Style\GraphicStyle;

class BasicItemTest extends TestCase
{
    private $page;

    public function setup(): void
    {
        $report = new Report($this->dataLayoutFile('empty_A4P.tlf'));
        $this->page = $report->addPage();
    }

    private function newBasicItem($schema_data_name): BasicItem
    {
        return new BasicItem($this->page, $this->dataItemFormat($schema_data_name));
    }

    public function test_initialize(): void
    {
        $item = $this->newBasicItem('image');
        $this->assertAttributeInstanceOf(BasicStyle::class,
            'style', $item);

        $item = $this->newBasicItem('text');
        $this->assertAttributeInstanceOf(TextStyle::class,
            'style', $item);

        foreach (array('line', 'rect', 'ellipse') as $schema_data_name) {
            $item = $this->newBasicItem($schema_data_name);
            $this->assertAttributeInstanceOf(GraphicStyle::class,
                'style', $item);
        }
    }

    public function test_getBounds(): void
    {
        $item = $this->newBasicItem('image');
        $item_schema = $this->dataItemFormat('image');

        $this->assertEquals(array(
            'x'      => $item_schema['x'],
            'y'      => $item_schema['y'],
            'width'  => $item_schema['width'],
            'height' => $item_schema['height']
        ), $item->getBounds());

        $item = $this->newBasicItem('rect');
        $item_schema = $this->dataItemFormat('rect');

        $this->assertEquals(array(
            'x'      => $item_schema['x'],
            'y'      => $item_schema['y'],
            'width'  => $item_schema['width'],
            'height' => $item_schema['height']
        ), $item->getBounds());

        $item = $this->newBasicItem('text');
        $item_schema = $this->dataItemFormat('text');

        $this->assertEquals(array(
            'x' => $item_schema['x'],
            'y' => $item_schema['y'],
            'width' => $item_schema['width'],
            'height' => $item_schema['height']
        ), $item->getBounds());

        $item = $this->newBasicItem('ellipse');
        $item_schema = $this->dataItemFormat('ellipse');

        $this->assertEquals(array(
            'cx' => $item_schema['cx'],
            'cy' => $item_schema['cy'],
            'rx' => $item_schema['rx'],
            'ry' => $item_schema['ry']
        ), $item->getBounds());

        $item = $this->newBasicItem('line');
        $item_schema = $this->dataItemFormat('line');

        $this->assertEquals(array(
            'x1' => $item_schema['x1'],
            'y1' => $item_schema['y1'],
            'x2' => $item_schema['x2'],
            'y2' => $item_schema['y2']
        ), $item->getBounds());
    }

    public function test_isTypeOf(): void
    {
        $item = $this->newBasicItem('rect');

        $this->assertTrue($item->isTypeOf('basic'));
        $this->assertTrue($item->isTypeOf('rect'));
    }

    public function test_isImage(): void
    {
        $item = $this->newBasicItem('image');
        $this->assertTrue($item->isImage());

        $this->assertFalseIn(array('rect', 'ellipse', 'line', 'text'), 'isImage');
    }

    public function test_isText(): void
    {
        $item = $this->newBasicItem('text');
        $this->assertTrue($item->isText());

        $this->assertFalseIn(array('rect', 'ellipse', 'line', 'image'), 'isText');
    }

    public function test_isRect(): void
    {
        $item = $this->newBasicItem('rect');
        $this->assertTrue($item->isRect());

        $this->assertFalseIn(array('ellipse', 'line', 'image', 'text'), 'isRect');
    }

    public function test_isEllipse(): void
    {
        $item = $this->newBasicItem('ellipse');
        $this->assertTrue($item->isEllipse());

        $this->assertFalseIn(array('rect', 'line', 'image', 'text'), 'isEllipse');
    }

    public function test_isLine(): void
    {
        $item = $this->newBasicItem('line');
        $this->assertTrue($item->isLine());

        $this->assertFalseIn(array('rect', 'ellipse', 'image', 'text'), 'isLine');
    }

    private function assertFalseIn($schema_data_names, $method_name): void
    {
        foreach ($schema_data_names as $schema_data_name) {
            $item = $this->newBasicItem($schema_data_name);
            $this->assertFalse($item->$method_name());
        }
    }
}
