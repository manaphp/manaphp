<?php
namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Http\Validator;
use ManaPHP\Http\Validator\Exception;
use PHPUnit\Framework\TestCase;

class HttpValidatorTest extends TestCase
{
    /**
     * @var \ManaPHP\Http\ValidatorInterface
     */
    public $filter;
    protected $_di;

    public function setUp()
    {
        parent::setUp();
        $this->_di = new FactoryDefault();
        $this->filter = new Validator();
        $this->filter->setDi($this->_di);
    }

    public function test_bool()
    {
        $this->assertTrue($this->filter->validate('open', 'bool', '1'));
        $this->assertTrue($this->filter->validate('open', 'bool', 'true'));
        $this->assertTrue($this->filter->validate('open', 'bool', true));

        $this->assertFalse($this->filter->validate('open', 'bool', '0'));
        $this->assertFalse($this->filter->validate('open', 'bool', 'false'));
        $this->assertFalse($this->filter->validate('open', 'bool', false));

        try {
            $this->filter->validate('open', 'bool', 'd');
            $this->assertFalse('why not?');
        } catch (Exception $e) {
        }
    }

    public function test_int()
    {
        $this->assertEquals(18, $this->filter->validate('age', 'int', 18));
        $this->assertEquals(18, $this->filter->validate('age', 'int', '18'));
        $this->assertEquals(-18, $this->filter->validate('age', 'int', '-18'));

        try {
            $this->filter->validate('age', 'int', 'A18');
            $this->assertFalse('why not?');
        } catch (Exception $e) {
        }
    }

    public function test_float()
    {
        $this->assertEquals(1.8, $this->filter->validate('age', 'float', '1.8'));
        $this->assertEquals(-1.8, $this->filter->validate('age', 'float', '-1.8'));
        $this->assertEquals(0.8, $this->filter->validate('age', 'float', '.8'));
        $this->assertEquals(-0.8, $this->filter->validate('age', 'float', '-.8'));

        try {
            $this->filter->validate('age', 'float', 'A8');
            $this->assertFalse('why not?');
        } catch (Exception $e) {

        }
    }

    public function test_date()
    {
        $this->assertEquals('2016-6-6', $this->filter->validate('from', 'date', '2016-6-6'));

        try {
            $this->filter->validate('from', 'date', 'qq');
            $this->fail('why not?');
        } catch (\ManaPHP\Http\Validator\Exception $e) {

        }
    }

    public function test_range()
    {
        $this->assertEquals(18, $this->filter->validate('age', 'range:0,100', 18));
        $this->assertEquals(18, $this->filter->validate('age', 'range:0,100', '18'));

        try {
            $this->filter->validate('age', 'range:0,100', 'A18');
            $this->assertFalse('why not?');
        } catch (Exception $e) {

        }

        try {
            $this->filter->validate('age', 'range:0,100', '-1');
            $this->assertFalse('why not?');
        } catch (Exception $e) {

        }
    }

    public function test_min()
    {
        $this->assertEquals(18, $this->filter->validate('age', 'min:14', 18));

        try {
            $this->filter->validate('age', 'min:14', 13);
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_max()
    {
        $this->assertEquals(18, $this->filter->validate('age', 'max:24', 18));

        try {
            $this->filter->validate('age', 'max:24', 18);
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_minLength()
    {
        $this->assertEquals('mana', $this->filter->validate('', 'minLength:4', 'mana'));
        try {
            $this->filter->validate('', 'minLength:40', 'mana');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_maxLength()
    {
        $this->assertEquals('mana', $this->filter->validate('', 'maxLength:4', 'mana'));

        try {
            $this->filter->validate('', 'maxLength:2', 'mana');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_length()
    {
        $this->assertEquals('mana', $this->filter->validate('', 'length:0,4', 'mana'));

        try {
            $this->filter->validate('', 'length:0,2', 'mana');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_equal()
    {
        $this->assertEquals('mana', $this->filter->validate('', 'equal:mana', 'mana'));

        try {
            $this->filter->validate('', 'equal:m', 'mana');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_regex()
    {
        $this->assertEquals('123', $this->filter->validate('id', 'regex:#^\d+$#', '123'));
        try {
            $this->filter->validate('id', 'regex:#^\d+$#', 'A123');
            $this->fail('why not?');
        } catch (\ManaPHP\Http\Validator\Exception $e) {

        }
    }

    public function test_alpha()
    {
        $this->assertEquals('mana', $this->filter->validate('nickname', 'alpha', 'mana'));

        try {
            $this->filter->validate('nickname', 'alpha', '1');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_digit()
    {
        $this->assertEquals('123', $this->filter->validate('id', 'digit', '123'));

        try {
            $this->filter->validate('id', 'digit', 'd');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_alnum()
    {
        $this->assertEquals('mana', $this->filter->validate('nickname', 'alnum', 'mana'));
        $this->assertEquals('123', $this->filter->validate('id', 'alnum', '123'));

        try {
            $this->filter->validate('id', 'alnum', 'd');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_upper()
    {
        $this->assertEquals('MANA', $this->filter->validate('', 'upper', 'mana'));
    }

    public function test_lower()
    {
        $this->assertEquals('mana', $this->filter->validate('', 'lower', 'MANA'));
    }

    public function test_account()
    {
        $this->assertEquals('mana', $this->filter->validate('name', 'account', 'mana'));
        try {
            $this->filter->validate('name', 'account', '1mana');
            $this->fail('why not?');
        } catch (\ManaPHP\Http\Validator\Exception $e) {

        }
    }

    public function test_password()
    {
        $this->assertEquals('666', $this->filter->validate('pwd', 'password', '666'));
        try {
            $this->filter->validate('pwd', 'password', '');
            $this->fail('why not?');
        } catch (\ManaPHP\Http\Validator\Exception $e) {

        }
    }

    public function test_email()
    {
        $this->assertEquals('admin@manaphp.com', $this->filter->validate('user_email', 'email', 'admin@manaphp.com'));

        try {
            $this->filter->validate('user_email', 'email', 'admin');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_ip()
    {
        $this->assertEquals('1.2.3.4', $this->filter->validate('ip', 'ip', '1.2.3.4'));

        try {
            $this->filter->validate('ip', 'ip', '1.2.3');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {

        }
    }

    public function test_url()
    {
        $this->assertEquals('http://www.baidu.com/', $this->filter->validate('from', 'url', 'http://www.baidu.com/'));

        try {
            $this->filter->validate('from', 'url', 'mana');
            $this->fail('why not?');
        } catch (\ManaPHP\Http\Validator\Exception $e) {

        }
    }

    public function test_mobile()
    {
        $this->assertEquals('13345678901', $this->filter->validate('mobile', 'mobile', '13345678901'));

        try {
            $this->filter->validate('mobile', 'mobile', '1334567');
            $this->fail('why not?');
        } catch (\ManaPHP\Http\Validator\Exception $e) {

        }
    }
}