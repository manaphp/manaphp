<?php

namespace Tests;

use ManaPHP\Text\Crossword;
use PHPUnit\Framework\TestCase;

class TextCrosswordTest extends TestCase
{
    public function test_guess()
    {
        $crossword = new Crossword();
        $this->assertEquals('Home', $crossword->guess(['Admin', 'Home', 'Api'], 'o'));
        $this->assertEquals('Home', $crossword->guess(['Admin', 'Home', 'Api'], 'home'));
        $this->assertEquals('Home', $crossword->guess(['Admin', 'Home', 'Api'], 'oe'));
        $this->assertFalse($crossword->guess(['Admin', 'Home', 'Api'], 'a'));
        $this->assertFalse($crossword->guess(['Admin', 'Home', 'Api'], 's'));

    }
}