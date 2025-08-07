<?php
namespace Thinreports\Item;

use ReflectionClass;
use Thinreports\Page\Page;
use Thinreports\TestCase;
use Thinreports\Report;
use Thinreports\Item\Style\TextStyle;

class PageNumberItemTest extends TestCase
{
    private Page $page;
    private Report $report;

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

        $reflection = new ReflectionClass($test_item);
        $property = $reflection->getProperty('style');
        $property->setAccessible(true);
        $this->assertInstanceOf(TextStyle::class, $property->getValue($test_item));
        $this->assertEquals('{page}', $reflection->getProperty('number_format')->getValue($test_item));
        $this->assertTrue($reflection->getProperty('is_dynamic')->getValue($test_item));
    }

    public function test_setNumberFormat(): void
    {
        $test_item = $this->newPageNumber('default');
        $test_item->setNumberFormat('{page} / {total}');

        $reflection = new ReflectionClass($test_item);
        $this->assertEquals('{page} / {total}', $reflection->getProperty('number_format')->getValue($test_item));
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
