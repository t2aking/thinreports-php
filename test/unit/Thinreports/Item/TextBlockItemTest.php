<?php
namespace Thinreports\Item;

use Thinreports\Exception\StandardException;
use Thinreports\TestCase;
use Thinreports\Report;
use Thinreports\Layout;
use Thinreports\Page\Page;
use Thinreports\Exception;
use Thinreports\Item\Style\TextStyle;
use Thinreports\Item\TextFormatter;
use Thinreports\Item\TextBlockItem;

class TextBlockItemTest extends TestCase
{
    private $page;

    public function setup(): void
    {
        $text_block_formats = $this->dataItemFormatsFor('text_block');

        $layout_filename = $this->dataLayoutFile('empty_A4P.tlf');
        $report = new Report($layout_filename);
        $layout = new Layout(array('items' => $text_block_formats), 'identifier');

        $this->page = new Page($report, $layout, 1);
    }

    private function newTextBlock($data_format_key): TextBlockItem
    {
        $format = $this->dataItemFormat('text_block', $data_format_key);
        return new TextBlockItem($this->page, $format);
    }

    public function test_initialize(): void
    {
        $text_block = $this->newTextBlock('default');

        $this->assertAttributeInstanceOf(TextStyle::class, 'style', $text_block);
        $this->assertAttributeInstanceOf(TextFormatter::class, 'formatter', $text_block);
        $this->assertAttributeSame('', 'value', $text_block);
        $this->assertAttributeSame(false, 'format_enabled', $text_block);
        $this->assertAttributeSame(null, 'reference_item', $text_block);

        $text_block = $this->newTextBlock('with_default_value');

        $this->assertAttributeSame('10000', 'value', $text_block);

        $text_block = $this->newTextBlock('with_reference_to_default');

        $this->assertAttributeInstanceOf(TextBlockItem::class,
            'reference_item', $text_block);
    }

    /**
     * @throws StandardException
     */
    public function test_setValue(): void
    {
        $text_block = $this->newTextBlock('default');

        $text_block->setValue('New value');
        $this->assertAttributeEquals('New value', 'value', $text_block);

        $this->assertSame($text_block, $text_block->setValue('foo'));

        $text_block = $this->newTextBlock('with_reference_to_default');

        try {
            $text_block->setValue('New value');
            $this->fail();
        } catch (Exception\StandardException $e) {
            $this->assertEquals('Readonly Item', $e->getSubject());
        }
    }

    /**
     * @throws StandardException
     */
    public function test_getValue(): void
    {
        $text_block = $this->newTextBlock('with_default_value');

        $this->assertEquals('10000', $text_block->getValue());
        $text_block->setValue(9999);
        $this->assertEquals(9999, $text_block->getValue());

        $text_block_default = $this->page->item('text_block_default');
        $text_block_refs    = $this->page->item('text_block_reference_to_default');

        $text_block_default->setValue(123456);
        $this->assertEquals(123456, $text_block_refs->getValue());

        $text_block_default->setValue('foo');
        $this->assertEquals('foo', $text_block_refs->getValue());
    }

    /**
     * @throws StandardException
     */
    public function test_setFormatEnabled(): void
    {
        $text_block = $this->newTextBlock('with_number_formatting');

        $text_block->setFormatEnabled(false);
        $this->assertAttributeSame(false, 'format_enabled', $text_block);

        $text_block->setFormatEnabled(true);
        $this->assertAttributeSame(true, 'format_enabled', $text_block);

        $this->assertSame($text_block, $text_block->setFormatEnabled(false));

        $text_block = $this->newTextBlock('default');

        try {
            $text_block->setFormatEnabled(true);
            $this->fail();
        } catch (Exception\StandardException $e) {
            $this->assertEquals('Not Formattable', $e->getSubject());
        }

        $text_block = $this->newTextBlock('with_multiple');

        try {
            $text_block->setFormatEnabled(true);
            $this->fail();
        } catch (Exception\StandardException $e) {
            $this->assertEquals('Not Formattable', $e->getSubject());
        }
    }

    /**
     * @throws StandardException
     */
    public function test_isFormatEnabled(): void
    {
        $text_block = $this->newTextBlock('with_number_formatting');
        $this->assertTrue($text_block->isFormatEnabled());

        $text_block->setFormatEnabled(false);
        $this->assertFalse($text_block->isFormatEnabled());
    }

    /**
     * @throws StandardException
     */
    public function test_getRealValue(): void
    {
        $text_block = $this->newTextBlock('with_number_formatting');
        $text_block->setValue(1000);

        $this->assertEquals('1,000.0', $text_block->getRealValue());

        $text_block->setFormatEnabled(false);
        $this->assertEquals(1000, $text_block->getRealValue());

        $text_block = $this->newTextBlock('default');
        $text_block->setValue(1000);

        $this->assertEquals(1000, $text_block->getRealValue());
    }

    public function test_isMultiple(): void
    {
        $text_block = $this->newTextBlock('with_multiple');
        $this->assertTrue($text_block->isMultiple());

        $text_block = $this->newTextBlock('default');
        $this->assertFalse($text_block->isMultiple());
    }

    public function test_hasReference(): void
    {
        $text_block = $this->newTextBlock('with_reference_to_default');
        $this->assertTrue($text_block->hasReference());

        $text_block = $this->newTextBlock('default');
        $this->assertFalse($text_block->hasReference());
    }

    public function test_hasFormatSettings(): void
    {
        $text_block = $this->newTextBlock('with_number_formatting');
        $this->assertTrue($text_block->hasFormatSettings());

        $text_block = $this->newTextBlock('with_base_formatting');
        $this->assertTrue($text_block->hasFormatSettings());

        $text_block = $this->newTextBlock('default');
        $this->assertFalse($text_block->hasFormatSettings());
    }
}
