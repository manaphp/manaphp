<?php
defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class AuthorizationAclTest extends TestCase
{
    public function test_allow()
    {
        $acl = new \ManaPHP\Authorization\Acl();
        $this->assertFalse($acl->isAllowed('index:index', 1));

        $acl->allow(1, 'index');
        $this->assertTrue($acl->isAllowed('index::index', 1));
        $this->assertTrue($acl->isAllowed('index::ddd', 1));

    }
}