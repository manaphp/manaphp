<?php

namespace Tests;

use ManaPHP\Helper\Str;
use PHPUnit\Framework\TestCase;

class HelpStrTest extends TestCase
{
    public function test_contains()
    {
        $this->assertTrue(Str::contains('manaphp', 'mana'));
        $this->assertTrue(Str::contains('manaphp', 'manaphp'));
        $this->assertFalse(Str::contains('manaphp', 'ManaPHP'));

        $this->assertTrue(Str::contains('manaphp', 'Mana', true));
        $this->assertTrue(Str::contains('manaphp', 'Manaphp', true));
    }

    public function test_startsWith()
    {
        $this->assertTrue(Str::startsWith('manaphp', 'mana'));
        $this->assertTrue(Str::startsWith('manaphp', 'manaphp'));
        $this->assertFalse(Str::startsWith('manaphp', 'Mana'));

        $this->assertTrue(Str::startsWith('manaphp', 'Mana', true));
        $this->assertTrue(Str::startsWith('manaphp', 'Manaphp', true));
    }

    public function test_endsWith()
    {
        $this->assertTrue(Str::endsWith('manaphp', 'php'));
        $this->assertTrue(Str::endsWith('manaphp', 'manaphp'));
        $this->assertFalse(Str::endsWith('manaphp', 'mana'));

        $this->assertTrue(Str::endsWith('manaphp', 'PHP', true));
        $this->assertTrue(Str::endsWith('manaphp', 'Manaphp', true));
    }

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
