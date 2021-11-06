<?php

namespace Tests;

use ManaPHP\Helper\Str;
use PHPUnit\Framework\TestCase;

class HelpStrTest extends TestCase
{
    public function test_underscore()
    {
        $this->assertEquals('raw_scaled_scorer', Str::snakelize('RawScaledScorer'));
        $this->assertEquals('egg_and_ham', Str::snakelize('egg_and_ham'));
        $this->assertEquals('fancy_category', Str::snakelize('fancyCategory'));
    }

    public function test_camelize()
    {
        $this->assertEquals('ManaDocs', Str::pascalize('mana_docs'));
        $this->assertEquals('Mana', Str::pascalize('mana'));
    }
}
