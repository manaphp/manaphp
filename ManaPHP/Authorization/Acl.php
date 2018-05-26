<?php

namespace ManaPHP\Authorization;

use ManaPHP\Authorization\Acl\Exception as AclException;
use ManaPHP\AuthorizationInterface;
use ManaPHP\Component;
use ManaPHP\Di;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Authorization\Acl
 *
 * @package ManaPHP\Authorization
 *
 * @property \ManaPHP\Security\IdentityInterface $identity
 */
class Acl extends Component implements AuthorizationInterface, \Serializable
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
            $controller = basename(strtr($controller, '\\', '/'), 'Controller');
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

        $roleId = ',' . ($roleId ?: $this->identity->getRoleId()) . ',';
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

    /**
     * @return string
     */
    public function serialize()
    {
        return json_encode($this->_acl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $this->_acl = json_decode($serialized, true);

        $this->_di = Di::getDefault();
    }
}