<?php
namespace Thinreports\Generator\PDF;

use Com\Tecnick\File\File;
use Com\Tecnick\Pdf\Font\Stack;
use PHPUnit\Framework\Attributes\DataProvider;
use Thinreports\TestCase;

class FontMetricsTest extends TestCase
{
    public static function fontProvider(): array
    {
        return [
            'IPA Mincho' => ['ipam', 10.5, 0, 100],
            'IPA P Gothic, stretched' => ['ipagp', 7, 1.5, 80],
            'Helvetica' => ['helvetica', 12, 0, 100],
        ];
    }

    #[DataProvider('fontProvider')]
    public function test_getCharBBox_matches_tc_lib_stack(string $family, float $size, float $spacing, float $stretching): void
    {
        $expected = $this->insert(new Stack(kunit: 1, subset: true, fileHelper: self::files()), $family, $size, $spacing, $stretching);
        $actual = $this->insert(new FontMetrics(kunit: 1, subset: true, fileHelper: self::files()), $family, $size, $spacing, $stretching);

        // ASCII, Japanese, a full-width digit, and characters the font does not have.
        foreach ([0x41, 0x67, 0x20, 0x65E5, 0x6F22, 0xFF11, 0x1F600, 0x10FFFF] as $ord) {
            $this->assertSame($expected->getCharBBox($ord), $actual->getCharBBox($ord), sprintf('U+%04X', $ord));
        }
    }

    public function test_metrics_do_not_keep_scaled_glyph_boxes(): void
    {
        $font = $this->insert(new FontMetrics(kunit: 1, subset: true, fileHelper: self::files()), 'ipam', 10, 0, 100);

        $metric = $font->getCurrentFont();
        $this->assertSame([], $metric['cbbox']);
        $this->assertSame([], $metric['cbboxu']);
        // Widths are still scaled and cached, as text layout reads them for each character.
        $this->assertNotEmpty($metric['cw']);
    }

    private static function files(): File
    {
        return new File(allowedPaths: [Font::getGeneratedPath()]);
    }

    private function insert(Stack $font, string $family, float $size, float $spacing, float $stretching): Stack
    {
        $objnum = 0;
        $font->insert($objnum, $family, '', $size, $spacing, $stretching / 100,
            ifile: Font::getGeneratedPath() . '/' . $family . '.json');
        return $font;
    }
}
