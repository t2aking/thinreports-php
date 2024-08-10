<?php
namespace Thinreports\Page;

use Thinreports\Exception\StandardException;
use Thinreports\Item\PageNumberItem;
use Thinreports\TestCase;
use Thinreports\Report;
use Thinreports\Layout;
use Thinreports\Item;
use Thinreports\Exception;
use Thinreports\Item\TextBlockItem;
use Thinreports\Item\ImageBlockItem;
use Thinreports\Item\BasicItem;

class PageTest extends TestCase
{
    private $report;
    private $layout;
    private $item_schemas;

    public function setup(): void
    {
        $this->item_schemas = $this->dataItemFormats(array(
            array('text_block', 'default'),
            array('image_block', 'default'),
            array('page_number', 'default'),
            array('text', 'default')
        ));

        $this->report = new Report($this->dataLayoutFile('empty_A4P.tlf'));
        $this->layout = new Layout(array('items' => $this->item_schemas),'dummy.tlf');
    }

    private function newPage($is_countable = true): Page
    {
        return new Page($this->report, $this->layout, 1, $is_countable);
    }

    public function test_isBlank(): void
    {
        $page = $this->newPage();
        $this->assertFalse($page->isBlank());
    }

    public function test_isCountable(): void
    {
        $page = $this->newPage();
        $this->assertTrue($page->isCountable());

        $page = $this->newPage(false);
        $this->assertFalse($page->isCountable());
    }

    /**
     * @throws StandardException
     */
    public function test_item(): void
    {
        $page = $this->newPage();

        try {
            $page->item('unknown_id');
            $this->fail();
        } catch (Exception\StandardException $e) {
            $this->assertEquals('Item Not Found', $e->getSubject());
        }

        $this->assertAttributeCount(0, 'items', $page);

        $this->assertInstanceOf(TextBlockItem::class,
            $page->item('text_block_default'));
        $this->assertInstanceOf(ImageBlockItem::class,
            $page->item('image_block_default'));
        $this->assertInstanceOf(PageNumberItem::class,
            $page->item('page_number_default'));
        $this->assertInstanceOf(BasicItem::class,
            $page->item('text_default'));

        $this->assertAttributeCount(4, 'items', $page);
    }

    /**
     * @throws StandardException
     */
    public function test_invoke(): void
    {
        $page = $this->newPage();

        try {
            $page('unknown_id');
            $this->fail();
        } catch (Exception\StandardException $e) {
            $this->assertEquals('Item Not Found', $e->getSubject());
        }

        $this->assertInstanceOf(TextBlockItem::class,
            $page->item('text_block_default'));

        $this->assertSame($page->item('text_block_default'),
            $page('text_block_default'));
    }

    /**
     * @throws StandardException
     */
    public function test_setItemValue(): void
    {
        $page = $this->newPage();

        try {
            $page->setItemValue('text_default', 'content');
            $this->fail();
        } catch (Exception\StandardException $e) {
            $this->assertEquals('Undeniable Item', $e->getSubject());
        }

        $page->setItemValue('text_block_default', 'value');
        $this->assertEquals('value', $page->item('text_block_default')->getValue());

        $page->setItemValue('image_block_default', 'value');
        $this->assertEquals('value', $page->item('image_block_default')->getValue());
    }

    /**
     * @throws StandardException
     */
    public function test_setItemValues(): void
    {
        $page = $this->newPage();

        try {
            $page->setItemValues(array('text_default' => 'value'));
            $this->fail();
        } catch (Exception\StandardException $e) {
            $this->assertEquals('Undeniable Item', $e->getSubject());
        }

        $page->setItemValues(array(
            'text_block_default'  => 'value',
            'image_block_default' => 'value'
        ));

        $this->assertEquals('value', $page('text_block_default')->getValue());
        $this->assertEquals('value', $page('image_block_default')->getValue());
    }

    public function test_hasItem(): void
    {
        $page = $this->newPage();

        $this->assertTrue($page->hasItem('text_block_default'));
        $this->assertTrue($page->hasItem('image_block_default'));
        $this->assertTrue($page->hasItem('page_number_default'));
        $this->assertTrue($page->hasItem('text_default'));

        $this->assertFalse($page->hasItem('unknown_id'));
    }

    public function test_getReport(): void
    {
        $page = $this->newPage();

        $this->assertSame($this->report, $page->getReport());
    }

    public function test_getLayout(): void
    {
        $page = $this->newPage();

        $this->assertSame($this->layout, $page->getLayout());
    }

    /**
     * @throws StandardException
     */
    public function test_getFinalizedItems(): void
    {
        $page = $this->newPage();

        $expects = array(
            new Item\TextBlockItem($page, $this->item_schemas['text_block_default']),
            new Item\ImageBlockItem($page, $this->item_schemas['image_block_default']),
            new Item\PageNumberItem($page, $this->item_schemas['page_number_default']),
            new Item\BasicItem($page, $this->item_schemas['text_default'])
        );
        $this->assertEquals($expects, $page->getFinalizedItems());
    }
}
