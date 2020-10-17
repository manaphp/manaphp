<?php /** @noinspection PhpParamsInspection */

namespace Tests;

use ManaPHP\Db;
use ManaPHP\Mvc\Factory;
use ManaPHP\Validator;
use ManaPHP\Validator\ValidateFailedException;
use PHPUnit\Framework\TestCase;
use Tests\Models\City;
use Tests\Models\Customer;
use Tests\Models\Payment;

class ValidatorTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $di = new Factory();

        $di->alias->set('@data', __DIR__ . '/tmp/data');
        $config = require __DIR__ . '/config.database.php';
        $di->setShared('db', new Db($config['mysql']));
    }

    public function test_bool()
    {
        $validator = new Validator();

        $this->assertSame(1, $validator->validateValue('open', '1', ['bool']));

        $this->assertSame(1, $validator->validateValue('open', '1', 'bool'));
        $this->assertSame(1, $validator->validateValue('open', 'true', 'bool'));
        $this->assertSame(1, $validator->validateValue('open', 'yes', 'bool'));
        $this->assertSame(1, $validator->validateValue('open', 'on', 'bool'));
        $this->assertSame(1, $validator->validateValue('open', true, 'bool'));

        $this->assertSame(0, $validator->validateValue('open', '0', ['bool']));

        $this->assertSame(0, $validator->validateValue('open', '0', 'bool'));
        $this->assertSame(0, $validator->validateValue('open', 'false', 'bool'));
        $this->assertSame(0, $validator->validateValue('open', 'no', 'bool'));
        $this->assertSame(0, $validator->validateValue('open', 'off', 'bool'));
        $this->assertSame(0, $validator->validateValue('open', false, 'bool'));

        $this->expectException(ValidateFailedException::class);
        $this->expectExceptionMessage('open');
        $validator->validateValue('open', 'd', 'bool');

        $customer = new  Customer();
        $customer->active = 1;
        $this->assertSame(1, $validator->validateModel('active', $customer, 'bool'));

        $customer->active = 'on';
        $this->assertSame(1, $validator->validateModel('active', $customer, 'bool'));
        $this->assertSame($customer->active, 1);

        $customer->active = 'off';
        $this->assertSame(0, $validator->validateModel('active', $customer, 'bool'));
        $this->assertSame($customer->active, 0);

        $customer->active = 'abc';
        $this->expectException(ValidateFailedException::class);
        $validator->validateModel('active', $customer, 'bool');
    }

    public function test_int()
    {
        $validator = new Validator();

        $this->assertSame(18, $validator->validateValue('age', 18, 'int'));
        $this->assertSame(18, $validator->validateValue('age', '18', 'int'));
        $this->assertSame(-18, $validator->validateValue('age', '-18', 'int'));

        $this->expectException(ValidateFailedException::class);
        $validator->validateValue('age', 'A18', 'int');

        $city = new City();

        $city->city_id = '100';
        $this->assertSame(100, $validator->validateModel('city_id', $city, 'int'));
        $this->assertSame($city->city_id, 100);

        $city->city_id = 'xxx';
        $this->expectException(ValidateFailedException::class);
        $validator->validateModel('city_id', $city, 'int');
    }

    public function test_string()
    {
        $validator = new Validator();

        $this->assertSame('18', $validator->validateValue('age', 18, 'string'));
        $this->assertSame('18', $validator->validateValue('age', '18', 'string'));
        $this->assertSame('-18.09', $validator->validateValue('age', -18.09, 'string'));

        $city = new City();
        $city->city_id = 100;
        $this->assertSame('100', $validator->validate('city_id', $city, 'string'));
        $this->assertSame($city->city_id, '100');

        $city->city_id = '100';
        $this->assertSame('100', $validator->validate('city_id', $city, 'string'));
        $this->assertSame($city->city_id, '100');
    }

    public function test_float()
    {
        $validator = new Validator();

        $this->assertSame(1.8, $validator->validateValue('age', '1.8', 'float'));
        $this->assertSame(-1.8, $validator->validateValue('age', '-1.8', 'float'));
        $this->assertSame(0.8, $validator->validateValue('age', '.8', 'float'));
        $this->assertSame(-0.8, $validator->validateValue('age', '-.8', 'float'));

        $this->expectException(ValidateFailedException::class);
        $validator->validateValue('age', 'A8', 'float');


        $payment = $this->createMock(Payment::class);

        $payment->method('rules')->willReturn(['amount' => 'float']);
        $payment->method('getIntFields')->willReturn([]);

        $payment->amount = 1.25;
        $payment->validate();
        $this->assertSame(1.25, $payment->amount);

        $payment->amount = '1.25';
        $payment->validate();
        $this->assertSame(1.25, $payment->amount);

        $payment->amount = '-1.25';
        $payment->validate();
        $this->assertSame(-1.25, $payment->amount);

        $payment->amount = '+1.25';
        $payment->validate();
        $this->assertSame(1.25, $payment->amount);

        $payment->amount = 'xxx';

        $this->expectException(ValidateFailedException::class);
        $validator->validate('', $payment, ['amount']);
    }

    public function test_range()
    {
        $validator = new Validator();

        $this->assertSame(18, $validator->validateValue('age', 18, ['range' => '0-100']));
        $this->assertSame(18, $validator->validateValue('age', '18', ['range' => '0-100']));

        $this->expectException(ValidateFailedException::class);
        $validator->validateValue('age', 'A18', ['range' => '0-100']);

        $this->expectException(ValidateFailedException::class);
        $validator->validateValue('age', '-1', ['range' => '0-100']);

        $city = $this->createMock(City::class);
        $city->method('rules')->willReturn(['city_id' => ['range' => '3-100']]);

        $city->city_id = 0;
        $this->expectException(ValidateFailedException::class);
        $city->validate();

        $city->city_id = 3;
        $city->validate();
        $this->assertSame(3, $city->city_id);

        $city->city_id = 10;
        $validator->validate('city_id', $city);
        $this->assertSame(10, $city->city_id);

        $city->city_id = 100;
        $city->validate();
        $this->assertSame(100, $city->city_id);

        $city->city_id = 101;
        $this->expectException(ValidateFailedException::class);
        $city->validate();

        $city = $this->createMock(City::class);
        $city->city_id = -1;
        $city->method('rules')->willReturn(['city_id' => ['range' => '-10.0-100.13']]);
        $city->validate();
        $this->assertSame(-1, $city->city_id);

        $city = $this->createMock(City::class);
        $city->city_id = -11;
        $city->method('rules')->willReturn(['city_id' => ['range' => '-100--10']]);
        $city->validate();
        $this->assertSame(-11, $city->city_id);
    }

    public function test_min()
    {
        $validator = new Validator();

        $this->assertSame(18, $validator->validateValue('age', 18, ['min' => '14']));

        $this->expectException(ValidateFailedException::class);
        $validator->validateValue('age', 13, ['min' => 14]);

        $city = $this->createMock(City::class);
        $city->method('rules')->willReturn(['city_id' => ['min' => 10]]);

        $city->city_id = 0;
        $this->expectException(ValidateFailedException::class);
        $validator->validate('city_id', $city, ['city_id']);

        $city->city_id = 10;
        $city->validate();
        $this->assertSame(10, $city->city_id);
    }

    public function test_max()
    {
        $validator = new Validator();

        $this->assertSame(18, $validator->validateValue('age', 18, ['max' => 24]));

        $this->expectException(ValidateFailedException::class);
        $validator->validateValue('age', 18, ['max' => 10]);

        $city = $this->createMock(City::class);
        $city->method('rules')->willReturn(['city_id' => ['max' => 10]]);

        $city->city_id = 11;
        $this->expectException(ValidateFailedException::class);
        $city->validate();

        $city->city_id = 10;
        $city->validate();
        $this->assertSame(10, $city->city_id);
    }

    public function test_minLength()
    {
        $validator = new Validator();

        $this->assertSame('mana', $validator->validateValue('', 'mana', ['minLength' => 4]));

        $this->expectException(ValidateFailedException::class);
        $validator->validateValue('', 'mana', ['minLength' => 40]);
    }

    public function test_maxLength()
    {
        $validator = new Validator();

        $this->assertSame('mana', $validator->validateValue('', 'mana', ['maxLength' => 4]));

        $this->expectException(ValidateFailedException::class);
        $validator->validateValue('', 'mana', ['maxLength' => 2]);
    }

    public function test_length()
    {
        $validator = new Validator();

        $this->assertSame('mana', $validator->validateValue('', 'mana', ['length' => '0-4']));

        $this->expectException(ValidateFailedException::class);
        $validator->validateValue('', 'mana', ['length' => '0-2']);
    }

    public function test_regex()
    {
        $validator = new Validator();

        $this->assertSame('123', $validator->validateValue('id', '123', ['regex' => '#^\d+$#']));

        $this->expectException(ValidateFailedException::class);
        $validator->validateValue('id', 'A123', ['regex' => '#^\d+$#']);
    }

    public function test_alpha()
    {
        $validator = new Validator();

        $this->assertSame('mana', $validator->validateValue('nickname', 'mana', 'alpha'));

        $this->expectException(ValidateFailedException::class);
        $validator->validateValue('nickname', '1', 'alpha');
    }

    public function test_digit()
    {
        $validator = new Validator();

        $this->assertSame('123', $validator->validateValue('id', '123', 'digit'));

        $this->expectException(ValidateFailedException::class);
        $validator->validateValue('id', 'd', 'digit');
    }

    public function test_alnum()
    {
        $validator = new Validator();

        $this->assertSame('mana', $validator->validateValue('nickname', 'mana', 'alnum'));
        $this->assertSame('123', $validator->validateValue('id', '123', 'alnum'));

        $this->expectException(ValidateFailedException::class);
        $validator->validateValue('id', '-', 'alnum');
    }

    public function test_upper()
    {
        $validator = new Validator();

        $this->assertSame('MANA', $validator->validateValue('', 'mana', 'upper'));
    }

    public function test_lower()
    {
        $validator = new Validator();

        $this->assertSame('mana', $validator->validateValue('', 'MANA', 'lower'));
    }

    public function test_account()
    {
        $validator = new Validator();

        $this->assertSame('mana', $validator->validateValue('name', 'mana', 'account'));

        $this->expectException(ValidateFailedException::class);
        $validator->validateValue('name', '1mana', 'account');
    }

    public function test_email()
    {
        $validator = new Validator();

        $this->assertSame('admin@manaphp.com', $validator->validateValue('user_email', 'admin@manaphp.com', 'email'));

        $this->expectException(ValidateFailedException::class);
        $this->expectExceptionMessage('user_email');
        $validator->validateValue('user_email', 'admin', 'email');
    }

    public function test_ip()
    {
        $validator = new Validator();

        $this->assertSame('1.2.3.4', $validator->validateValue('ip', '1.2.3.4', 'ip'));

        $this->expectException(ValidateFailedException::class);
        $this->expectExceptionMessage('ip');
        $validator->validateValue('ip', '1.2.3', 'ip');
    }

    public function test_url()
    {
        $validator = new Validator();

        $this->assertSame('http://www.baidu.com/', $validator->validateValue('from', 'http://www.baidu.com/', 'url'));

        $this->expectException(ValidateFailedException::class);
        $this->expectExceptionMessage('from');
        $validator->validateValue('from', 'mana', 'url');
    }

    public function test_mobile()
    {
        $validator = new Validator();

        $this->assertSame('13345678901', $validator->validateValue('mobile', '13345678901', 'mobile'));

        $this->expectException(ValidateFailedException::class);
        $this->expectExceptionMessage('mobile');
        $validator->validateValue('mobile', '1334567', 'mobile');
    }

    public function test_date()
    {
        $validator = new Validator();

        $this->assertSame('2016-6-6', $validator->validateValue('from', '2016-6-6', 'date'));

        $this->expectException(ValidateFailedException::class);
        $validator->validateValue('from', 'qq', 'date');

        $ts = time();

        //timestamp
        $city = $this->createMock(City::class);
        $city->method('rules')->willReturn(['last_update' => 'date']);
        $city->method('getIntFields')->willReturn(['last_update']);

        $city->last_update = $ts;
        $city->validate();
        $this->assertSame($ts, $city->last_update);

        $city->last_update = (string)$ts;
        $city->validate();
        $this->assertSame($ts, $city->last_update);

        $city->last_update = date('Y-m-d H:i:s', $ts);
        $city->validate();
        $this->assertSame($ts, $city->last_update);

        $city->last_update = strtotime('-10seconds', $ts);
        $city->validate();
        $this->assertSame($ts - 10, $city->last_update);

        //string
        $city = new City();

        $city->last_update = $ts;
        $city->validate();
        $this->assertEquals(date('Y-m-d H:i:s', $ts), $city->last_update);

        $city->last_update = (string)$ts;
        $city->validate();
        $this->assertEquals(date('Y-m-d H:i:s', $ts), $city->last_update);

        $city->last_update = date('Y-m-d H:i:s', $ts);
        $city->validate();
        $this->assertEquals(date('Y-m-d H:i:s', $ts), $city->last_update);

        $city->last_update = strtotime('-10seconds', $ts);
        $city->validate();
        $this->assertSame(date('Y-m-d H:i:s', $ts - 10), $city->last_update);

        //with format
        $city = $this->createMock(City::class);
        $city->method('rules')->willReturn(['last_update' => ['date' => 'Y-m-d']]);
        $city->method('getIntFields')->willReturn([]);

        $city->last_update = date('Y-m-d H:i:s', $ts);
        $city->validate();
        $this->assertEquals(date('Y-m-d', $ts), $city->last_update);
    }

    public function test_exists()
    {
        $validator = new Validator();

        $city = new City();

        $city->country_id = 2;
        $validator->validateModel('country_id', $city, ['exists']);

        $city->country_id = -2;
        $this->expectException(ValidateFailedException::class);
        $validator->validateModel('country_id', $city, ['exists']);
    }
}