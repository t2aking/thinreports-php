<?php
namespace Thinreports\Item;

use Thinreports\TestCase;
use Thinreports\Report;

class TestBlockItem extends AbstractBlockItem {}

class AbstractBlockItemTest extends TestCase
{
    private TestBlockItem $test_item;

    public function setup(): void
    {
        $report = new Report($this->dataLayoutFile('empty_A4P.tlf'));
        $parent = $report->addPage();
        $schema = array(
            'type' => 'test-block',
            'display' => true,
            'x' => 100,
            'y' => 100,
            'width' => 100,
            'height' => 100
        );
        $this->test_item = new TestBlockItem($parent, $schema);
    }

    public function test_setValue(): void
    {
        $this->test_item->setValue(1000);
        $this->assertEquals(1000, $this->test_item->getValue());
    }

    public function test_getValue(): void
    {
        $this->test_item->setValue(9999);
        $this->assertEquals(9999, $this->test_item->getValue());
    }

    public function test_isEmpty(): void
    {
        $this->test_item->setValue('');
        $this->assertTrue($this->test_item->isEmpty());

        $this->test_item->setValue(null);
        $this->assertTrue($this->test_item->isEmpty());

        $this->test_item->setValue(0);
        $this->assertFalse($this->test_item->isEmpty());

        $this->test_item->setValue('0');
        $this->assertFalse($this->test_item->isEmpty());

        $this->test_item->setValue(1000);
        $this->assertFalse($this->test_item->isEmpty());
    }

    public function test_isPresent(): void
    {
        $this->test_item->setValue('');
        $this->assertFalse($this->test_item->isPresent());

        $this->test_item->setValue(null);
        $this->assertFalse($this->test_item->isPresent());

        $this->test_item->setValue(0);
        $this->assertTrue($this->test_item->isPresent());

        $this->test_item->setValue('0');
        $this->assertTrue($this->test_item->isPresent());

        $this->test_item->setValue(1000);
        $this->assertTrue($this->test_item->isPresent());
    }

    public function test_getBounds(): void
    {
        $this->assertSame(
            array('x' => 100, 'y' => 100, 'width' => 100, 'height' => 100),
            $this->test_item->getBounds()
        );
    }

    public function test_isTypeOf(): void
    {
        $this->assertTrue($this->test_item->isTypeOf('test-block'));
    }
}
