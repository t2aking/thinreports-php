<?php
namespace Thinreports;

use ReflectionClass;
use ReflectionException;
use Thinreports\Exception;
use Thinreports\Exception\StandardException;
use Thinreports\Item;

class LayoutTest extends TestCase
{
    public function test_loadFile(): void
    {
        try {
            Layout::loadFile('nonexistent.tlf');
            $this->fail();
        } catch (Exception\StandardException $e) {
            $this->assertEquals('Layout File Not Found', $e->getSubject());
        }

        try {
            $layout = Layout::loadFile($this->dataLayoutFile('empty_A4P.tlf'));
        } catch (StandardException $e) {
            $this->fail($e->getMessage());
        }

        $this->assertNotNull($layout);

        try {
            $layout = Layout::loadFile($this->dataLayoutFile('empty_A4P'));
        } catch (StandardException $e) {
            $this->fail($e->getMessage());
        }

        $this->assertNotNull($layout);
    }

    /**
     * @throws ReflectionException
     */
    public function test_loadData(): void
    {
        $schema_data = '{"version":"0.10.1","items":[]}';
        try {
            $layout = Layout::loadData($schema_data);
        } catch (Exception\IncompatibleLayout $e) {
            $this->fail($e->getMessage());
        }

        $this->assertNotNull($layout);
        $reflection = new ReflectionClass($layout);
        $property = $reflection->getProperty('identifier');
        $property->setAccessible(true);
        $this->assertEquals(md5($schema_data), $property->getValue($layout));
    }

    public function test_parse(): void
    {
        try {
            Layout::parse('{"version":"0.8.1"}');
            $this->fail();
        } catch (Exception\IncompatibleLayout $e) {
            // OK
        }

        try {
            Layout::parse('{"version":"1.0.0"}');
            $this->fail();
        } catch (Exception\IncompatibleLayout $e) {
            // OK
        }

        try {
            $schema = Layout::parse('{"version":"0.9.0", "items":[]}');
        } catch (Exception\IncompatibleLayout $e) {
            $this->fail($e->getMessage());
        }

        $this->assertSame(array('version' => '0.9.0', 'items' => array()), $schema);

        try {
            $schema = Layout::parse('{"version":"0.9.0", "items":[{"id": "", "type": "image", "x": 0.0, "y": 0.0, "width": 592.6, "height": 764.5, "display": true}]}');
        } catch (Exception\IncompatibleLayout $e) {
            $this->fail($e->getMessage());
        }

        $items = [
            [
                'id' => '',
                'type' => 'image',
                'x' => 0.0,
                'y' => 0.0,
                'width' => 592.6,
                'height' => 764.5,
                'display' => true
            ]
        ];
        $this->assertSame(array('version' => '0.9.0', 'items' => $items), $schema);

        try {
            $schema = Layout::parse('{"version":"0.9.0", "items":[{"id": "test_id", "type": "image", "x": 0.0, "y": 0.0, "width": 592.6, "height": 764.5, "display": true}]}');
        } catch (Exception\IncompatibleLayout $e) {
            $this->fail($e->getMessage());
        }

        $items = [
            'test_id' => [
                'id' => 'test_id',
                'type' => 'image',
                'x' => 0.0,
                'y' => 0.0,
                'width' => 592.6,
                'height' => 764.5,
                'display' => true
            ]
        ];
        $this->assertSame(array('version' => '0.9.0', 'items' => $items), $schema);
    }

    public function test_initialize(): void
    {
        $schema = array(
            'version' => '0.10.1',
            'items' => array(
                array('id' => '', 'type' => 'rect'),
                array('id' => 'foo', 'type' => 'text-block'),
                array('id' => 'bar', 'type' => 'text'),
                array('id' => '', 'type' => 'line')
            )
        );

        $layout = new Layout($schema, 'layout_identifier');

        $reflection = new ReflectionClass($layout);
        $property = $reflection->getProperty('schema');
        $property->setAccessible(true);
        $this->assertEquals($schema, $property->getValue($layout));
        $property = $reflection->getProperty('identifier');
        $property->setAccessible(true);
        $this->assertEquals('layout_identifier', $property->getValue($layout));
        $property = $reflection->getProperty('item_schemas');
        $property->setAccessible(true);
        $this->assertEquals(
            array(
                'with_id' => array(
                    'foo' => array('id' => 'foo', 'type' => 'text-block'),
                    'bar' => array('id' => 'bar', 'type' => 'text')
                ),
                'without_id' => array(
                    array('id' => '', 'type' => 'rect'),
                    array('id' => '', 'type' => 'line')
                )
            ),
            $property->getValue($layout)
        );
    }

    public function test_hasItemById(): void
    {
        $item_schemas = array(
            array('id' => 'foo', 'type' => 'rect'),
            array('id' => 'bar', 'type' => 'text-block')
        );
        $layout = new Layout(array('items' => $item_schemas), 'identifier');

        $this->assertTrue($layout->hasItemById('bar'));
        $this->assertFalse($layout->hasItemById('unknown'));
    }

    public function test_createItem(): void
    {
        $this->markTestSkipped('Item classes are not supported yet.');

        $item_formats = $this->dataItemFormats(array(
            array('text_block', 'default'),
            array('image_block', 'default'),
            array('page_number', 'default'),
            array('rect', 'default'),
            array('ellipse', 'default'),
            array('line', 'default'),
            array('image', 'default'),
            array('text', 'default')
        ));

        $layout = new Layout('dummy.tlf', array(
            'format' => array('svg' => '<svg></svg>'),
            'item_formats' => $item_formats
        ));

        $dummy_report = new Report($this->dataLayoutFile('empty_A4P.tlf'));
        $dummy_page   = $dummy_report->addPage();

        $this->assertInstanceOf(
            'Thinreports\Item\TextBlockItem',
            $layout->createItem($dummy_page, 'text_block_default')
        );
        $this->assertInstanceOf(
            'Thinreports\Item\ImageBlockItem',
            $layout->createItem($dummy_page, 'image_block_default')
        );
        $this->assertInstanceOf(
            'Thinreports\Item\PageNumberItem',
            $layout->createItem($dummy_page, '__page_no_1__')
        );

        $graphic_ids = array(
            'rect_default',
            'ellipse_default',
            'line_default',
            'image_default',
            'text_default'
        );
        foreach ($graphic_ids as $id) {
            $this->assertInstanceOf(
                'Thinreports\Item\BasicItem',
                $layout->createItem($dummy_page, $id)
            );
        }

        try {
            $layout->createItem($dummy_page, 'unknown_id');
            $this->fail();
        } catch (Exception\StandardException $e) {
            $this->assertEquals('Item Not Found', $e->getSubject());
        }
    }

    /**
     * Tests for:
     *      Layout::getLastVersion
     *      Layout::getReportTitle
     *      Layout::getPagePaperType
     *      Layout::isUserPaperType
     *      Layout::isPortraitPage
     *      Layout::getPageSize
     *      Layout::getSVG
     */
    public function test_schema_attribute_getters(): void
    {
        $schema = array(
            'version' => '0.10.1',
            'title' => 'Report Title',
            'report' => array(
                'paper-type'  => 'A4',
                'orientation' => 'landscape',
            ),
            'items' => array()
        );

        $layout = new Layout($schema, 'identifier');

        $this->assertEquals('0.10.1', $layout->getLastVersion());
        $this->assertEquals('Report Title', $layout->getReportTitle());
        $this->assertEquals('A4', $layout->getPagePaperType());
        $this->assertFalse($layout->isUserPaperType());
        $this->assertFalse($layout->isPortraitPage());
        $this->assertNull($layout->getPageSize());

        $schema = array(
            'report' => array(
                'paper-type'  => 'user',
                'orientation' => 'portrait',
                'width'       => 100.9,
                'height'      => 999.9
            ),
            'items' => array()
        );

        $layout = new Layout($schema, 'identifier');

        $this->assertEquals('user', $layout->getPagePaperType());
        $this->assertTrue($layout->isUserPaperType());
        $this->assertEquals(array(100.9, 999.9), $layout->getPageSize());
    }

    public function test_getIdentifier(): void
    {
        $layout = new Layout(array('items' => array()), 'identifier');
        $this->assertEquals('identifier', $layout->getIdentifier());
    }

    public function test_getSchema(): void
    {
        $schema = array('version' => '0.10.1', 'items' => array());
        $layout = new Layout($schema, 'identifier');
        $this->assertSame($schema, $layout->getSchema());
    }

    public function test_getItemSchemas(): void
    {
        $item_schemas = array(
            array('id' => 'text1', 'type' => 'text-block'),
            array('id' => '', 'type' => 'rect')
        );

        $layout = new Layout(array('items' => $item_schemas), 'identifier');

        $this->assertSame($item_schemas, $layout->getItemSchemas());
        $this->assertSame($item_schemas, $layout->getItemSchemas('all'));
        $this->assertEquals(
            array('text1' => array('id' => 'text1', 'type' => 'text-block')),
            $layout->getItemSchemas('with_id')
        );
        $this->assertEquals(
            array(array('id' => '', 'type' => 'rect')),
            $layout->getItemSchemas('without_id')
        );
        $this->assertEquals(
            array(),
            $layout->getItemSchemas('unknown')
        );
    }
}
