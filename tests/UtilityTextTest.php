<?php
namespace Tests;

use ManaPHP\Utility\Text;
use PHPUnit\Framework\TestCase;

class UtilityTextTest extends TestCase
{
    public function test_contains()
    {
        $this->assertTrue(Text::contains('manaphp', 'mana'));
        $this->assertTrue(Text::contains('manaphp', 'manaphp'));
        $this->assertFalse(Text::contains('manaphp', 'ManaPHP'));

        $this->assertTrue(Text::contains('manaphp', 'Mana', true));
        $this->assertTrue(Text::contains('manaphp', 'Manaphp', true));
    }

    public function test_startsWith()
    {
        $this->assertTrue(Text::startsWith('manaphp', 'mana'));
        $this->assertTrue(Text::startsWith('manaphp', 'manaphp'));
        $this->assertFalse(Text::startsWith('manaphp', 'Mana'));

        $this->assertTrue(Text::startsWith('manaphp', 'Mana', true));
        $this->assertTrue(Text::startsWith('manaphp', 'Manaphp', true));
    }

    public function test_endsWith()
    {
        $this->assertTrue(Text::endsWith('manaphp', 'php'));
        $this->assertTrue(Text::endsWith('manaphp', 'manaphp'));
        $this->assertFalse(Text::endsWith('manaphp', 'mana'));

        $this->assertTrue(Text::endsWith('manaphp', 'PHP', true));
        $this->assertTrue(Text::endsWith('manaphp', 'Manaphp', true));
    }

    public function test_underscore()
    {
        $this->assertEquals('raw_scaled_scorer', Text::underscore('RawScaledScorer'));
        $this->assertEquals('egg_and_ham', Text::underscore('egg_and_ham'));
        $this->assertEquals('fancy_category', Text::underscore('fancyCategory'));
    }

    public function test_camelize()
    {
        $this->assertEquals('ManaDocs', Text::camelize('mana_docs'));
        $this->assertEquals('Mana', Text::camelize('mana'));
    }
}
