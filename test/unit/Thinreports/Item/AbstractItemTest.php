<?php
namespace Thinreports\Item;

use Thinreports\TestCase;
use Thinreports\Report;
use Thinreports\Page\Page;
use Thinreports\Item\Style\GraphicStyle;

class TestItem extends AbstractItem
{
    public function getBounds(): array
    {
        return array();
    }
}

class TestGraphicsItem extends AbstractItem
{
    // make public for testing
    public $style;

    public function __construct(Page $parent, array $schema)
    {
        parent::__construct($parent, $schema);
        $this->style = new GraphicStyle($schema);
    }

    public function getBounds(): array
    {
        return array();
    }
}

class AbstractItemTest extends TestCase
{
    private $page;

    public function setup(): void
    {
        $report = new Report($this->dataLayoutFile('empty_A4P.tlf'));
        $this->page = $report->addPage();
    }

    public function test_initialize(): void
    {
        $item = new TestItem($this->page, array('display' => true));
        $this->assertTrue($item->isVisible());

        $item = new TestItem($this->page, array('display' => false));
        $this->assertFalse($item->isVisible());
    }

    /**
     * Tests for:
     *      AbstractItem::isVisible
     *      AbstractItem::setVisible
     *      AbstractItem::show
     *      AbstractItem::hide
     */
    public function test_methods_for_visibility(): void
    {
        $item = new TestItem($this->page, array('display' => true));

        $item->setVisible(false);
        $this->assertFalse($item->isVisible());

        $item->setVisible(true);
        $this->assertTrue($item->isVisible());

        $item->hide();
        $this->assertFalse($item->isVisible());

        $item->show();
        $this->assertTrue($item->isVisible());

        $this->assertSame($item, $item->setVisible(true));
    }

    public function test_setStyle(): void
    {
        $item = new TestGraphicsItem($this->page, $this->dataItemFormat('rect'));

        $item->setStyle('fill_color', 'red');
        $this->assertEquals('red', $item->style->get_fill_color());
    }

    public function test_getStyle(): void
    {
        $item = new TestGraphicsItem($this->page, $this->dataItemFormat('rect'));
        $this->assertEquals('#ffffff', $item->getStyle('fill_color'));
        $this->assertEquals('1', $item->getStyle('border_width'));
    }

    public function test_setStyles(): void
    {
        $item = new TestGraphicsItem($this->page, $this->dataItemFormat('rect'));

        $item->setStyles(array('fill_color' => 'blue', 'border_width' => 999));
        $this->assertEquals('blue', $item->style->get_fill_color());
        $this->assertEquals(999, $item->style->get_border_width());
    }

    public function test_exportStyles(): void
    {
        $item = new TestGraphicsItem($this->page, $this->dataItemFormat('rect'));
        $item->style->set_fill_color('#0000ff');

        $this->assertEquals($item->style->export(), $item->exportStyles());
    }

    public function test_getParent(): void
    {
        $item = new TestItem($this->page, array('display' => true));
        $this->assertSame($this->page, $item->getParent());
    }

    public function test_getIsDynamic(): void
    {
        $item = new TestItem($this->page, array('id' => '', 'display' => true));
        $this->assertFalse($item->isDynamic());

        $item = new TestItem($this->page, array('id' => 'foo', 'display' => true));
        $this->assertTrue($item->isDynamic());
    }

    public function test_getSchema(): void
    {
        $schema = array('display' => true);
        $item = new TestItem($this->page, $schema);
        $this->assertSame($schema, $item->getSchema());
    }

    public function test_getId(): void
    {
        $item = new TestItem($this->page, array(
            'display' => true,
            'id' => 'foo_id'
        ));

        $this->assertEquals('foo_id', $item->getId());
    }

    public function test_isTypeOf(): void
    {
        $item = new TestItem($this->page, array(
            'display' => true,
            'type' => 'foo_type'
        ));
        $this->assertTrue($item->isTypeOf('foo_type'));
    }
}
