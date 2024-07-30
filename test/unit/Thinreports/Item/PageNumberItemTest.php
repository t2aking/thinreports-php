<?php
namespace Thinreports\Item;

use Thinreports\TestCase;
use Thinreports\Report;
use Thinreports\Layout;
use Thinreports\Item\Style\TextStyle;

class PageNumberItemTest extends TestCase
{
    private $page;
    private $report;

    public function setup(): void
    {
        $this->report = new Report($this->dataLayoutFile('empty_A4P.tlf'));
        $this->page = $this->report->addPage();
    }

    private function newPageNumber($data_format_key): PageNumberItem
    {
        $schema = $this->dataItemFormat('page_number', $data_format_key);
        return new PageNumberItem($this->page, $schema);
    }

    public function test_initialize(): void
    {
        $test_item = $this->newPageNumber('default');

        $this->assertAttributeInstanceOf(TextStyle::class,
            'style', $test_item);
        $this->assertAttributeEquals('{page}', 'number_format', $test_item);
        $this->assertAttributeEquals(true, 'is_dynamic', $test_item);
    }

    public function test_setNumberFormat(): void
    {
        $test_item = $this->newPageNumber('default');
        $test_item->setNumberFormat('{page} / {total}');

        $this->assertAttributeEquals('{page} / {total}', 'number_format', $test_item);
    }

    public function test_getNumberFormat(): void
    {
        $test_item = $this->newPageNumber('default');
        $this->assertEquals('{page}', $test_item->getNumberFormat());

        $test_item->setNumberFormat('-- {page} --');
        $this->assertEquals('-- {page} --', $test_item->getNumberFormat());
    }

    public function test_resetNumberFormat(): void
    {
        $test_item = $this->newPageNumber('default');

        $test_item->setNumberFormat('-- {page} --');
        $this->assertEquals('-- {page} --', $test_item->getNumberFormat());

        $test_item->resetNumberFormat();

        $this->assertEquals('{page}', $test_item->getNumberFormat());
    }

    public function test_getFormattedPageNumber(): void
    {
        $test_item = $this->newPageNumber('default');
        $test_item->setNumberFormat('');

        $this->assertSame('', $test_item->getFormattedPageNumber());

        $test_item = $this->newPageNumber('target_list');

        $this->assertSame('', $test_item->getFormattedPageNumber());

        $test_item = $this->newPageNumber('default');
        $test_item->setNumberFormat('{page} / {total}');

        $this->report->addPage();
        $this->report->addpage();

        $this->assertEquals('1 / 3', $test_item->getFormattedPageNumber());
    }

    public function test_isForReport(): void
    {
        $test_item = $this->newPageNumber('default');
        $this->assertTrue($test_item->isForReport());

        $test_item = $this->newPageNumber('target_blank');
        $this->assertTrue($test_item->isForReport());

        $test_item = $this->newPageNumber('target_list');
        $this->assertFalse($test_item->isForReport());
    }

    public function test_getBounds(): void
    {
        $test_item = $this->newPageNumber('default');

        $this->assertEquals(
            array('x' => 100, 'y' => 100, 'width' => 100, 'height' => 100),
            $test_item->getBounds()
        );
    }
}
