<?php
namespace ManaPHP\Authorization;

use ManaPHP\Authorization\Acl\Exception as AclException;
use ManaPHP\AuthorizationInterface;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Authorization\Acl
 *
 * @package ManaPHP\Authorization
 *
 * @property \ManaPHP\Authentication\UserIdentityInterface $userIdentity
 */
class Acl implements AuthorizationInterface
{
    /**
     * @var array[]
     */
    protected $_acl = [];

    /**
     * @param int|string|array $roleId
     * @param string           $controller
     * @param string|array     $actions
     *
     * @return static
     */
    public function allow($roleId, $controller, $actions = '*')
    {
        if (strpos($controller, '\\') !== false) {
            $controller = basename(end(explode('\\', $controller)), 'Controller');
        } else {
            $controller = Text::camelize($controller);
        }

        if (!isset($this->_acl[$controller])) {
            $this->_acl[$controller] = [];
        }

        $this->_acl[$controller][] = ',' . implode(',', (array)$roleId) . ',:,' . implode(',', (array)$actions) . ',';

        return $this;
    }

    /**
     * @param string     $permission
     * @param int|string $roleId
     *
     * @return bool
     * @throws \ManaPHP\Authorization\Acl\Exception
     */
    public function isAllowed($permission, $roleId = null)
    {
        $parts = explode('::', $permission);
        switch (count($parts)) {
            case 1:
                $controller = Text::camelize($parts[0]);
                $action = 'index';
                break;
            case 2:
                $controller = Text::camelize($parts[0]);
                $action = $parts[1];
                break;
            default:
                throw new AclException('ss');
        }

        $roleId = ',' . ($roleId ?: $this->userIdentity->getRoleId()) . ',';
        $action = ',' . $action . ',';

        if (isset($this->_acl['*'])) {
            foreach ($this->_acl['*'] as $item) {
                $parts = explode(':', $item);
                if (strpos($parts[0], $roleId) !== false) {
                    return true;
                }
            }
        }

        if (isset($this->_acl[$controller])) {
            foreach ($this->_acl[$controller] as $item) {
                $parts = explode(':', $item);
                if (strpos($parts[0], $roleId) !== false) {
                    if (strpos($parts[1], $action) !== false || strpos($parts[1], '*') !== false) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}