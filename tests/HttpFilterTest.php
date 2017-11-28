<?php
namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Http\Filter;
use ManaPHP\Http\Filter\Exception;
use PHPUnit\Framework\TestCase;

class HttpFilterTest extends TestCase
{
    /**
     * @var \ManaPHP\Http\FilterInterface
     */
    public $filter;
    protected $_di;

    public function setUp()
    {
        parent::setUp();
        $this->_di = new FactoryDefault();
        $this->filter = new Filter();
        $this->filter->setDependencyInjector($this->_di);
    }

    public function test_bool()
    {
        $this->assertTrue($this->filter->sanitize('open', 'bool', '1'));
        $this->assertTrue($this->filter->sanitize('open', 'bool', 'true'));
        $this->assertTrue($this->filter->sanitize('open', 'bool', true));

        $this->assertFalse($this->filter->sanitize('open', 'bool', '0'));
        $this->assertFalse($this->filter->sanitize('open', 'bool', 'false'));
        $this->assertFalse($this->filter->sanitize('open', 'bool', false));

        try {
            $this->filter->sanitize('open', 'bool', 'd');
            $this->assertFalse('why not?');
        } catch (Exception $e) {
        }
    }

    public function test_int()
    {
        $this->assertEquals(18, $this->filter->sanitize('age', 'int', 18));
        $this->assertEquals(18, $this->filter->sanitize('age', 'int', '18'));
        $this->assertEquals(-18, $this->filter->sanitize('age', 'int', '-18'));

        try {
            $this->filter->sanitize('age', 'int', 'A18');
            $this->assertFalse('why not?');
        } catch (Exception $e) {
        }
    }

    public function test_float()
    {
        $this->assertEquals(1.8, $this->filter->sanitize('age', 'float', '1.8'));
        $this->assertEquals(-1.8, $this->filter->sanitize('age', 'float', '-1.8'));
        $this->assertEquals(0.8, $this->filter->sanitize('age', 'float', '.8'));
        $this->assertEquals(-0.8, $this->filter->sanitize('age', 'float', '-.8'));

        try {
            $this->filter->sanitize('age', 'float', 'A8');
            $this->assertFalse('why not?');
        } catch (Exception $e) {

        }
    }

    public function test_date()
    {
        $this->assertEquals('2016-6-6', $this->filter->sanitize('from', 'date', '2016-6-6'));

        try {
            $this->filter->sanitize('from', 'date', 'qq');
            $this->fail('why not?');
        } catch (\ManaPHP\Http\Filter\Exception $e) {

        }
    }

    public function test_range()
    {
        $this->assertEquals(18, $this->filter->sanitize('age', 'range:0,100', 18));
        $this->assertEquals(18, $this->filter->sanitize('age', 'range:0,100', '18'));

        try {
            $this->filter->sanitize('age', 'range:0,100', 'A18');
            $this->assertFalse('why not?');
        } catch (Exception $e) {

        }

        try {
            $this->filter->sanitize('age', 'range:0,100', '-1');
            $this->assertFalse('why not?');
        } catch (Exception $e) {

        }
    }

    public function test_min()
    {
        $this->assertEquals(18, $this->filter->sanitize('age', 'min:14', 18));

        try {
            $this->filter->sanitize('age', 'min:14', 13);
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_max()
    {
        $this->assertEquals(18, $this->filter->sanitize('age', 'max:24', 18));

        try {
            $this->filter->sanitize('age', 'max:24', 18);
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_minLength()
    {
        $this->assertEquals('mana', $this->filter->sanitize('', 'minLength:4', 'mana'));
        try {
            $this->filter->sanitize('', 'minLength:40', 'mana');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_maxLength()
    {
        $this->assertEquals('mana', $this->filter->sanitize('', 'maxLength:4', 'mana'));

        try {
            $this->filter->sanitize('', 'maxLength:2', 'mana');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_length()
    {
        $this->assertEquals('mana', $this->filter->sanitize('', 'length:0,4', 'mana'));

        try {
            $this->filter->sanitize('', 'length:0,2', 'mana');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_equal()
    {
        $this->assertEquals('mana', $this->filter->sanitize('', 'equal:mana', 'mana'));

        try {
            $this->filter->sanitize('', 'equal:m', 'mana');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_regex()
    {
        $this->assertEquals('123', $this->filter->sanitize('id', 'regex:#^\d+$#', '123'));
        try {
            $this->filter->sanitize('id', 'regex:#^\d+$#', 'A123');
            $this->fail('why not?');
        } catch (\ManaPHP\Http\Filter\Exception $e) {

        }
    }

    public function test_alpha()
    {
        $this->assertEquals('mana', $this->filter->sanitize('nickname', 'alpha', 'mana'));

        try {
            $this->filter->sanitize('nickname', 'alpha', '1');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_digit()
    {
        $this->assertEquals('123', $this->filter->sanitize('id', 'digit', '123'));

        try {
            $this->filter->sanitize('id', 'digit', 'd');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_alnum()
    {
        $this->assertEquals('mana', $this->filter->sanitize('nickname', 'alnum', 'mana'));
        $this->assertEquals('123', $this->filter->sanitize('id', 'alnum', '123'));

        try {
            $this->filter->sanitize('id', 'alnum', 'd');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_upper()
    {
        $this->assertEquals('MANA', $this->filter->sanitize('', 'upper', 'mana'));
    }

    public function test_lower()
    {
        $this->assertEquals('mana', $this->filter->sanitize('', 'lower', 'MANA'));
    }

    public function test_account()
    {
        $this->assertEquals('mana', $this->filter->sanitize('name', 'account', 'mana'));
        try {
            $this->filter->sanitize('name', 'account', '1mana');
            $this->fail('why not?');
        } catch (\ManaPHP\Http\Filter\Exception $e) {

        }
    }

    public function test_password()
    {
        $this->assertEquals('666', $this->filter->sanitize('pwd', 'password', '666'));
        try {
            $this->filter->sanitize('pwd', 'password', '');
            $this->fail('why not?');
        } catch (\ManaPHP\Http\Filter\Exception $e) {

        }
    }

    public function test_email()
    {
        $this->assertEquals('admin@manaphp.com', $this->filter->sanitize('user_email', 'email', 'admin@manaphp.com'));

        try {
            $this->filter->sanitize('user_email', 'email', 'admin');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_ip()
    {
        $this->assertEquals('1.2.3.4', $this->filter->sanitize('ip', 'ip', '1.2.3.4'));

        try {
            $this->filter->sanitize('ip', 'ip', '1.2.3');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_json()
    {
        $this->assertEquals('1', $this->filter->sanitize('ids', 'json', '1'));
        $this->assertEquals([], $this->filter->sanitize('ids', 'json', '{}'));
        $this->assertEquals([1, 2], $this->filter->sanitize('ids', 'json', '[1,2]'));

        try {
            $this->filter->sanitize('ids', 'json', '[');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_url()
    {
        $this->assertEquals('http://www.baidu.com/', $this->filter->sanitize('from', 'url', 'http://www.baidu.com/'));

        try {
            $this->filter->sanitize('from', 'url', 'mana');
            $this->fail('why not?');
        } catch (\ManaPHP\Http\Filter\Exception $e) {

        }
    }

    public function test_mobile()
    {
        $this->assertEquals('13345678901', $this->filter->sanitize('mobile', 'mobile', '13345678901'));

        try {
            $this->filter->sanitize('mobile', 'mobile', '1334567');
            $this->fail('why not?');
        } catch (\ManaPHP\Http\Filter\Exception $e) {

        }
    }
}