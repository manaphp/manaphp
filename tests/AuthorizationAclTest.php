<?php
namespace Tests;

use ManaPHP\Authorization\Acl;
use PHPUnit\Framework\TestCase;

class AuthorizationAclTest extends TestCase
{
    public function test_allow()
    {
        $acl = new Acl();
        $this->assertFalse($acl->isAllowed('index:index', 1));

        $acl->allow(1, 'index');
        $this->assertTrue($acl->isAllowed('index::index', 1));
        $this->assertTrue($acl->isAllowed('index::ddd', 1));
    }
}