<?php
namespace Thinreports\Generator\PDF;

use Thinreports\TestCase;

class ColorParserTest extends TestCase
{
    /**
     * @dataProvider colorPatternProvider
     */
    public function test_parse($expected_result, $color): void
    {
        $actual = ColorParser::parse($color);
        $this->assertSame($expected_result, $actual);
    }

    public function colorPatternProvider(): array
    {
        return array(
            array(null, ''),
            array(null, null),
            array(array(0, 0, 0), '#000000'),
            array(array(0, 0, 0), '000000'),
            array(array(199, 184, 55), '#c7b837'),
            array(array(255, 0, 0), 'red'),
            array(array(255, 240, 0), 'yellow'),
            array(array(0, 0, 255), 'blue'),
            array(array(128, 128, 0), 'olive'),
            array(array(0, 0, 0), 'black'),
            array(array(192, 192, 192), 'silver'),
            array(array(255, 255, 255), 'white')
        );
    }
}
