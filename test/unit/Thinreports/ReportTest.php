<?php
namespace Thinreports;

use ReflectionClass;

class ReportTest extends TestCase
{
    private function createReport($layout_filename = null): Report
    {
        return new Report($layout_filename);
    }

    public function test_construct(): void
    {
        $default_layout_filename = $this->dataLayoutFile('empty_A4P.tlf');
        $report = $this->createReport($default_layout_filename);

        $this->assertNotNull($report->getDefaultLayout());
    }

    public function test_addPage_with_default_layout(): void
    {
        $default_layout_filename = $this->dataLayoutFile('empty_A4P.tlf');
        $report = $this->createReport($default_layout_filename);

        try {
            $page = $report->addPage();
        } catch (Exception\StandardException $e) {
            $this->fail($e->getMessage());
        }
        $this->assertNotNull($page);
        $this->assertTrue($page->isCountable());

        try {
            $page = $report->addPage(null, true);
        } catch (Exception\StandardException $e) {
            $this->fail($e->getMessage());
        }
        $this->assertTrue($page->isCountable());

        try {
            $page = $report->addPage(null, false);
        } catch (Exception\StandardException $e) {
            $this->fail($e->getMessage());
        }
        $this->assertFalse($page->isCountable());

        # Use other layout
        $other_layout_filename = $this->dataLayoutFile('empty_A4L.tlf');
        try {
            $page = $report->addPage($other_layout_filename);
        } catch (Exception\StandardException $e) {
            $this->fail($e->getMessage());
        }

        $this->assertNotNull($page);
        $this->assertTrue($page->isCountable());
    }

    public function test_addPage_without_default_layout(): void
    {
        $report = $this->createReport();

        # Not specify any layout
        try {
            $report->addPage();
            $this->fail();
        } catch (Exception\StandardException $e) {
            $this->assertEquals('Layout Not Specified', $e->getMessage());
        }

        $layout_filename1 = $this->dataLayoutFile('empty_A4P.tlf');
        try {
            $page = $report->addPage($layout_filename1);
        } catch (Exception\StandardException $e) {
            $this->fail($e->getMessage());
        }

        $this->assertNotNull($page);

        $layout_filename2 = $this->dataLayoutFile('empty_A4L.tlf');
        try {
            $page = $report->addPage($layout_filename2);
        } catch (Exception\StandardException $e) {
            $this->fail($e->getMessage());
        }
        $this->assertNotNull($page);
    }

    public function test_addBlankPage(): void
    {
        $report = $this->createReport();

        $page = $report->addBlankPage();
        $this->assertNotNull($page);
        $this->assertTrue($page->isCountable());

        $page = $report->addBlankPage(true);
        $this->assertTrue($page->isCountable());

        $page = $report->addBlankPage(false);
        $this->assertFalse($page->isCountable());
    }

    public function test_getPageCount(): void
    {
        $report = $this->createReport($this->dataLayoutFile('empty_A4P.tlf'));

        $this->assertEquals(0, $report->getPageCount());

        try {
            $report->addPage();
        } catch (Exception\StandardException $e) {
            $this->fail($e->getMessage());
        }
        $report->addBlankPage();

        $this->assertEquals(2, $report->getPageCount());

        try {
            $report->addPage(null, false);
        } catch (Exception\StandardException $e) {
            $this->fail($e->getMessage());
        }
        $report->addBlankPage(false);

        $this->assertEquals(2, $report->getPageCount());
    }

    public function test_getLastPageNumber(): void
    {
        $report = $this->createReport($this->dataLayoutFile('empty_A4P.tlf'));

        $this->assertEquals(0, $report->getLastPageNumber());

        try {
            $report->addPage();
        } catch (Exception\StandardException $e) {
            $this->fail($e->getMessage());
        }
        $report->addBlankPage();

        $this->assertEquals(2, $report->getLastPageNumber());
    }

    public function test_startPageNumberFrom(): void
    {
        $report = $this->createReport($this->dataLayoutFile('empty_A4P.tlf'));

        $report->startPageNumberFrom(5);

        $this->assertEquals(5, $report->addPage()->getNo());
        $this->assertEquals(6, $report->addPage()->getNo());

        $this->assertEquals(2, $report->getPageCount());
        $this->assertEquals(6, $report->getLastPageNumber());
    }

    public function test_getStartPageNumber(): void
    {
        $report = $this->createReport($this->dataLayoutFile('empty_A4P.tlf'));

        $this->assertEquals(1, $report->getStartPageNumber());

        $report->startPageNumberFrom(10);

        $this->assertEquals(10, $report->getStartPageNumber());
    }

    public function test_getPages(): void
    {
        $report = $this->createReport($this->dataLayoutFile('empty_A4P.tlf'));

        try {
            $pages = array(
                $report->addPage(),
                $report->addPage(),
                $report->addBlankPage()
            );
        } catch (Exception\StandardException $e) {
            $this->fail($e->getMessage());
        }
        $this->assertEquals($pages, $report->getPages());
    }

    public function test_getDefaultLayout(): void
    {
        $report = $this->createReport($this->dataLayoutFile('empty_A4P.tlf'));
        $reflection = new ReflectionClass($report);
        $property = $reflection->getProperty('default_layout');
        $this->assertEquals($report->getDefaultLayout(), $property->getValue($report));
    }

    public function test_buildLayout(): void
    {
        $report = $this->createReport();
        $layout_filename = $this->dataLayoutFile('empty_A4P.tlf');

        $reflection = new ReflectionClass($report);

        $property = $reflection->getProperty('layouts');
        $this->assertCount(0, $property->getValue($report));

        try {
            $layout1st = $report->buildLayout($layout_filename);
        } catch (Exception\StandardException $e) {
            $this->fail($e->getMessage());
        }
        $this->assertCount(1, $property->getValue($report));

        try {
            $layout2nd = $report->buildLayout($layout_filename);
        } catch (Exception\StandardException $e) {
            $this->fail($e->getMessage());
        }
        $this->assertCount(1, $property->getValue($report));
        $this->assertSame($layout1st, $layout2nd);
    }

    public function test_generateReturnsPdfDataWhenFilenameIsNull(): void
    {
        $report = $this->createReport($this->dataLayoutFile('empty_A4P.tlf'));
        $pdf_data = $report->generate();

        $this->assertNotNull($pdf_data);
    }

    public function test_generateWritesPdfToFileWhenFilenameIsProvided(): void
    {
        $report = $this->createReport($this->dataLayoutFile('empty_A4P.tlf'));
        $filename = 'test.pdf';
        $report->generate($filename);

        $this->assertTrue($report->generate($filename) > 0);
        $this->assertFileExists($filename);

        unlink($filename);
    }
}
