<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/18
 * Time: 22:07
 */

defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class HttpRequestTest extends TestCase
{
    public function test_get()
    {
        $request = new \ManaPHP\Http\Request();

        $this->assertNull($request->get('name'));

        $this->assertEquals('test', $request->get('name', null, 'test'));

        try {
            $this->assertEquals('test', $request->get('name', 'int'));
            $this->fail('why not?');
        } catch (\Exception $e) {
            $this->assertInstanceOf('ManaPHP\Http\Request\Exception', $e);
        }

        $_REQUEST['name'] = 'mana';
        $this->assertEquals('mana', $request->get('name'));
    }

    public function test_getGet()
    {
        $request = new \ManaPHP\Http\Request();

        $this->assertNull($request->getGet('name'));

        $this->assertEquals('test', $request->getGet('name', null, 'test'));

        try {
            $this->assertEquals('test', $request->getGet('name', 'int'));
            $this->fail('why not?');
        } catch (\Exception $e) {
            $this->assertInstanceOf('ManaPHP\Http\Request\Exception', $e);
        }

        $_GET['name'] = 'mana';
        $this->assertEquals('mana', $request->getGet('name'));
    }

    public function test_getPost()
    {
        $request = new \ManaPHP\Http\Request();

        $this->assertNull($request->getPost('name'));

        $this->assertEquals('test', $request->getPost('name', null, 'test'));

        try {
            $this->assertEquals('test', $request->getPost('name', 'int'));
            $this->fail('why not?');
        } catch (\Exception $e) {
            $this->assertInstanceOf('ManaPHP\Http\Request\Exception', $e);
        }

        $_POST['name'] = 'mana';
        $this->assertEquals('mana', $request->getPost('name'));
    }

    public function test_getPut()
    {
        $request = new \ManaPHP\Http\Request();

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $this->assertNull($request->getPut('name'));

        $this->assertEquals('test', $request->getPut('name', null, 'test'));

        try {
            $this->assertEquals('test', $request->getPut('name', 'int'));
            $this->fail('why not?');
        } catch (\Exception $e) {
            $this->assertInstanceOf('ManaPHP\Http\Request\Exception', $e);
        }
    }

    public function test_getQuery()
    {
        $request = new \ManaPHP\Http\Request();

        $this->assertNull($request->getQuery('name'));

        $this->assertEquals('test', $request->getQuery('name', null, 'test'));

        try {
            $this->assertEquals('test', $request->getQuery('name', 'int'));
            $this->fail('why not?');
        } catch (\Exception $e) {
            $this->assertInstanceOf('ManaPHP\Http\Request\Exception', $e);
        }

        $_GET['name'] = 'mana';
        $this->assertEquals('mana', $request->getQuery('name'));
    }

    public function test_getScheme()
    {
        $request = new \ManaPHP\Http\Request();

        try {
            $this->assertEquals('http', $request->getScheme());
            $this->fail('why not?');
        } catch (\Exception $e) {
            $this->assertInstanceOf('ManaPHP\Http\Request\Exception', $e);
        }

        $_SERVER['HTTPS'] = 'off';
        $this->assertEquals('http', $request->getScheme());

        $_SERVER['HTTPS'] = 'on';
        $this->assertEquals('https', $request->getScheme());
    }

    public function test_isAjax()
    {
        $request = new \ManaPHP\Http\Request();
        $this->assertFalse($request->isAjax());

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $this->assertTrue($request->isAjax());

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'ABC';
        $this->assertFalse($request->isAjax());
    }

    public function test_getRawBody()
    {

    }

    public function test_getClientAddress()
    {
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $request = new \ManaPHP\Http\Request();
        $this->assertEquals('1.2.3.4', $request->getClientAddress());

        //client address is public ip, we not trust the HTTP_X_FORWARDED_FOR

        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '5.6.7.8';
        $request = new \ManaPHP\Http\Request();
        $this->assertEquals('1.2.3.4', $request->getClientAddress());

        //client address is lookBack ip, we trust the HTTP_X_FORWARDED_FOR
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '5.6.7.8';
        $request = new \ManaPHP\Http\Request();
        $this->assertEquals('5.6.7.8', $request->getClientAddress());
        //client address is private ip, we trust the HTTP_X_FORWARDED_FOR
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '5.6.7.8';
        $request = new \ManaPHP\Http\Request();
        $this->assertEquals('5.6.7.8', $request->getClientAddress());

        //client address is private ip, we trust the HTTP_X_FORWARDED_FOR
        $_SERVER['REMOTE_ADDR'] = '10.0.1.2';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '5.6.7.8';
        $request = new \ManaPHP\Http\Request();
        $this->assertEquals('5.6.7.8', $request->getClientAddress());

        $request = new \ManaPHP\Http\Request();
        $request->setClientAddress('10.20.30.40');
        $this->assertEquals('10.20.30.40', $request->getClientAddress());
    }

    public function test_setClientAddress()
    {
        $request = new \ManaPHP\Http\Request();

        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $this->assertEquals('1.2.3.4', $request->getClientAddress());

        $request->setClientAddress('4.3.2.1');
        $this->assertEquals('4.3.2.1', $request->getClientAddress());

        $request->setClientAddress(function () {
            return '6.7.8.9';
        });
        $this->assertEquals('6.7.8.9', $request->getClientAddress());
    }

    public function test_getUserAgent()
    {
        $request = new \ManaPHP\Http\Request();

        $this->assertEquals('', $request->getUserAgent());

        $_SERVER['HTTP_USER_AGENT'] = 'IOS';
        $this->assertEquals('IOS', $request->getUserAgent());
    }

    public function test_isPost()
    {
        $request = new \ManaPHP\Http\Request();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertFalse($request->isPost());

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertTrue($request->isPost());
    }

    public function test_isGet()
    {
        $request = new \ManaPHP\Http\Request();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertFalse($request->isGet());

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertTrue($request->isGet());
    }

    public function test_isPut()
    {
        $request = new \ManaPHP\Http\Request();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertFalse($request->isPut());

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $this->assertTrue($request->isPut());
    }

    public function test_isHead()
    {
        $request = new \ManaPHP\Http\Request();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertFalse($request->isHead());

        $_SERVER['REQUEST_METHOD'] = 'HEAD';
        $this->assertTrue($request->isHead());
    }

    public function test_isDelete()
    {
        $request = new \ManaPHP\Http\Request();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertFalse($request->isDelete());

        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $this->assertTrue($request->isDelete());
    }

    public function test_isOptions()
    {
        $request = new \ManaPHP\Http\Request();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertFalse($request->isOptions());

        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $this->assertTrue($request->isOptions());
    }

    public function test_isPatch()
    {
        $request = new \ManaPHP\Http\Request();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertFalse($request->isPatch());

        $_SERVER['REQUEST_METHOD'] = 'PATCH';
        $this->assertTrue($request->isPatch());
    }

    public function test_getReferer()
    {
        $request = new \ManaPHP\Http\Request();

        $this->assertEquals('', $request->getReferer());

        $_SERVER['HTTP_REFERER'] = 'http://www.google.com/';
        $this->assertEquals('http://www.google.com/', $request->getReferer());
    }

    public function test_hasFiles()
    {
        $request = new \ManaPHP\Http\Request();

        $_FILES = array(
            'test' => array(
                'name' => 'name',
                'type' => 'text/plain',
                'size' => 1,
                'tmp_name' => 'tmp_name',
                'error' => 0,
            )
        );

        $this->assertEquals($request->hasFiles(false), 1);
        $this->assertEquals($request->hasFiles(true), 1);

        $_FILES = array(
            'test' => array(
                'name' => array('name1', 'name2'),
                'type' => array('text/plain', 'text/plain'),
                'size' => array(1, 1),
                'tmp_name' => array('tmp_name1', 'tmp_name2'),
                'error' => array(0, 0),
            )
        );

        $this->assertEquals($request->hasFiles(false), 2);
        $this->assertEquals($request->hasFiles(true), 2);

        $_FILES = array(
            'photo' => array(
                'name' => array(
                    0 => '',
                    1 => '',
                    2 => array(0 => '', 1 => '', 2 => ''),
                    3 => array(0 => ''),
                    4 => array(
                        0 => array(0 => ''),
                    ),
                    5 => array(
                        0 => array(
                            0 => array(
                                0 => array(0 => ''),
                            ),
                        ),
                    ),
                ),
                'type' => array(
                    0 => '',
                    1 => '',
                    2 => array(0 => '', 1 => '', 2 => ''),
                    3 => array(0 => ''),
                    4 => array(
                        0 => array(0 => ''),
                    ),
                    5 => array(
                        0 => array(
                            0 => array(
                                0 => array(0 => ''),
                            ),
                        ),
                    ),
                ),
                'tmp_name' => array(
                    0 => '',
                    1 => '',
                    2 => array(0 => '', 1 => '', 2 => ''),
                    3 => array(0 => ''),
                    4 => array(
                        0 => array(0 => ''),
                    ),
                    5 => array(
                        0 => array(
                            0 => array(
                                0 => array(0 => ''),
                            ),
                        ),
                    ),
                ),
                'error' => array(
                    0 => 4,
                    1 => 4,
                    2 => array(0 => 4, 1 => 4, 2 => 4),
                    3 => array(0 => 4),
                    4 => array(
                        0 => array(0 => 4),
                    ),
                    5 => array(
                        0 => array(
                            0 => array(
                                0 => array(0 => 4),
                            ),
                        ),
                    ),
                ),
                'size' => array(
                    0 => 0,
                    1 => 0,
                    2 => array(0 => 0, 1 => 0, 2 => 0),
                    3 => array(0 => 0),
                    4 => array(
                        0 => array(0 => 0),
                    ),
                    5 => array(
                        0 => array(
                            0 => array(
                                0 => array(0 => 0),
                            ),
                        ),
                    ),
                ),
            ),
            'test' => array(
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'error' => 4,
                'size' => 0,
            ),
        );

        $this->assertEquals($request->hasFiles(false), 9);
        $this->assertEquals($request->hasFiles(true), 0);
    }

    public function test_getFiles()
    {

        $request = new \ManaPHP\Http\Request();

        $_FILES = array(
            'fieldNameHere' => array(
                'name' => 'favicon.ico',
                'type' => 'image/x-icon',
                'tmp_name' => '/tmp/php7F4.tmp',
                'error' => 0,
                'size' => 202575,
            ),
        );

        $files = $request->getFiles();
        $this->assertCount(1, $files);

        $file = $files[0];
        $this->assertEquals('fieldNameHere', $file->getKey());
        $this->assertEquals('favicon.ico', $file->getName());
        $this->assertEquals('image/x-icon', $file->getType());
        $this->assertEquals('/tmp/php7F4.tmp', $file->getTempName());
        $this->assertEquals(0, $file->getError());
        $this->assertEquals(202575, $file->getSize());
        $this->assertEquals('ico', $file->getExtension());

        $_FILES = [
            'photo' => array(
                'name' => [0 => 'f0', 1 => 'f1'],
                'type' => [0 => 'text/plain', 1 => 'text/csv'],
                'tmp_name' => [0 => 't0', 1 => 't1'],
                'error' => [0 => 0, 1 => UPLOAD_ERR_NO_FILE],
                'size' => [0 => 10, 1 => 20],
            ),
        ];

        $all = $request->getFiles(false);
        $successful = $request->getFiles(true);
        $this->assertCount(2, $all);
        $this->assertCount(1, $successful);

        $this->assertEquals($all[0]->getName(), 'f0');
        $this->assertEquals($all[1]->getName(), 'f1');

        $this->assertEquals($all[0]->getTempName(), 't0');
        $this->assertEquals($all[1]->getTempName(), 't1');

        $this->assertEquals($successful[0]->getName(), 'f0');

        $this->assertEquals($successful[0]->getTempName(), 't0');
    }
}
