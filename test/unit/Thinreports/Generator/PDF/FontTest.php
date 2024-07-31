<?php
namespace Thinreports\Generator\PDF;

use Thinreports\TestCase;

class FontTest extends TestCase
{
    public function setup(): void
    {
        Font::$installed_builtin_fonts = array();
    }

    public function test_build(): void
    {
        Font::build();

        $this->assertEquals(
            array(
                'IPAMincho',
                'IPAPMincho',
                'IPAGothic',
                'IPAPGothic'
            ),
            array_keys(Font::$installed_builtin_fonts)
        );
    }

    public function test_getFontName(): void
    {
        $this->assertEquals('Helvetica', Font::getFontName('Helvetica'));

        $this->assertEquals('Courier', Font::getFontName('Courier New'));
        $this->assertEquals('Times', Font::getFontName('Times New Roman'));

        $this->assertFalse(Font::isInstalledFont('IPAMincho'));
        $this->assertNotContains('ipam', Font::$installed_builtin_fonts);

        $this->assertEquals('ipam', Font::getFontName('IPAMincho'));

        $this->assertTrue(Font::isInstalledFont('IPAMincho'));
        $this->assertContains('ipam', Font::$installed_builtin_fonts);

        $this->assertEquals('ipam', Font::getFontName('IPAMincho'));
    }

    /**
     * @dataProvider unicodeFontProvider
     */
    public function test_installBuiltinFont($expected_result, $font_name): void
    {
        $actual = Font::installBuiltinFont($font_name);

        $this->assertEquals($expected_result, $actual);
        $this->assertContains($actual, Font::$installed_builtin_fonts);
    }
    public function unicodeFontProvider(): array
    {
        return array(
            array('ipam', 'IPAMincho'),
            array('ipag', 'IPAGothic'),
            array('ipamp', 'IPAPMincho'),
            array('ipagp', 'IPAPGothic')
        );
    }

    public function test_isBuiltinUnicodeFont(): void
    {
        $this->assertFalse(Font::isBuiltinUnicodeFont('unknown font'));
        $this->assertFalse(Font::isBuiltinUnicodeFont('Helvetica'));
        $this->assertTrue(Font::isBuiltinUnicodeFont('IPAGothic'));
    }

    public function test_isInstalledFont(): void
    {
        $this->assertFalse(Font::isInstalledFont('IPAMincho'));
        Font::$installed_builtin_fonts['IPAMincho'] = 'ipam';
        $this->assertTrue(Font::isInstalledFont('IPAMincho'));
    }
}
