<?php

defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class TextCrosswordTest extends TestCase
{
    public function test_guess()
    {
        $crossword = new \ManaPHP\Text\Crossword();
        $this->assertEquals('Home', $crossword->guess(['Admin', 'Home', 'Api'], 'o'));
        $this->assertEquals('Home', $crossword->guess(['Admin', 'Home', 'Api'], 'home'));
        $this->assertEquals('Home', $crossword->guess(['Admin', 'Home', 'Api'], 'oe'));
        $this->assertFalse($crossword->guess(['Admin', 'Home', 'Api'], 'a'));
        $this->assertFalse($crossword->guess(['Admin', 'Home', 'Api'], 's'));

    }
}