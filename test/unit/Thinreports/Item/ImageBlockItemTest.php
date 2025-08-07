<?php
namespace Thinreports\Item;

use ReflectionClass;
use Thinreports\Page\Page;
use Thinreports\TestCase;
use Thinreports\Report;
use Thinreports\Item\Style\BasicStyle;

class ImageBlockItemTest extends TestCase
{
    private Page $page;

    public function setup(): void
    {
        $report = new Report($this->dataLayoutFile('empty_A4P.tlf'));
        $this->page = $report->addPage();
    }

    private function newImageBlock(): ImageBlockItem
    {
        $schema = $this->dataItemFormat('image_block', 'default');
        return new ImageBlockItem($this->page, $schema);
    }

    public function test_initialize(): void
    {
        $test_item = $this->newImageBlock();
        $reflection = new ReflectionClass($test_item);
        $property = $reflection->getProperty('style');
        $this->assertInstanceOf(BasicStyle::class, $property->getValue($test_item));
    }

    public function test_setSource(): void
    {
        $test_item = $this->newImageBlock();

        $test_item->setSource('/path/to/image.png');
        $this->assertEquals('/path/to/image.png', $test_item->getValue());
    }

    public function test_getSource(): void
    {
        $test_item = $this->newImageBlock();
        $this->assertSame('', $test_item->getSource());

        $test_item->setValue('/path/to/image.png');
        $this->assertEquals('/path/to/image.png', $test_item->getSource());
    }

    public function test_isTypeOf(): void
    {
        $test_item = $this->newImageBlock();
        $this->assertTrue($test_item->isTypeOf('image-block'));
        $this->assertFalse($test_item->isTypeOf('rect'));
    }
}
