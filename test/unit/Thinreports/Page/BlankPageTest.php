<?php
namespace Thinreports\Page;

use Thinreports\TestCase;

class BlankPageTest extends TestCase
{
    public function test_isCountable(): void
    {
        $blank_page = new BlankPage(1);
        $this->assertTrue($blank_page->isCountable());

        $blank_page = new BlankPage(1, false);
        $this->assertFalse($blank_page->isCountable());
    }

    public function test_isBlank(): void
    {
        $blank_page = new BlankPage(1);
        $this->assertTrue($blank_page->isBlank());
    }

    public function test_getNo(): void
    {
        $blank_page = new BlankPage(5);
        $this->assertEquals(5, $blank_page->getNo());
    }
}
