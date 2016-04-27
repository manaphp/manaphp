<?php
defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';
class HttpCookiesTest extends TestCase{
    /**
     * @var \ManaPHP\Http\Cookies
     */
    protected $_cookies;
    public function setUp()
    {
        $this->_cookies=new \ManaPHP\Http\Cookies();
        $this->_cookies->crypt=new ManaPHP\Crypt('abc');
    }

    public function test_set(){
        $this->_cookies->delete('name');

        $this->assertFalse($this->_cookies->has('name'));

        $this->_cookies->set('name','mana');
        $this->assertTrue($this->_cookies->has('name'));
        $this->assertEquals('mana',$this->_cookies->get('name'));

        $this->_cookies->set('!name','mana');
        $this->assertEquals('mana',$this->_cookies->get('!name'));
    }

    public function test_get(){
        $this->_cookies->delete('name');
        $this->assertEquals(null,$this->_cookies->get('name'));

        $this->_cookies->set('name','mana');
        $this->assertEquals('mana',$this->_cookies->get('name'));

        $this->_cookies->set('!name','mana');
        $this->assertEquals('mana',$this->_cookies->get('!name'));
    }

    public function test_has(){
        $this->_cookies->delete('name');

        $this->assertFalse($this->_cookies->has('name'));

        $this->_cookies->set('name','mana');
        $this->assertTrue($this->_cookies->has('name'));

        $this->_cookies->set('!name','mana');
        $this->assertTrue($this->_cookies->has('!name'));
    }

    public function test_delete(){
        $this->assertFalse($this->_cookies->has('missing'));
        $this->_cookies->delete('missing');

        $this->_cookies->set('name','mana');
        $this->assertTrue($this->_cookies->has('name'));
        $this->_cookies->delete('name');
    }
}