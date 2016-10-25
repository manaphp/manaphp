<?php
namespace ManaPHP\Authentication;

/**
 * Class ManaPHP\Authentication\UserIdentity
 *
 * @package userIdentity
 */
class UserIdentity implements UserIdentityInterface
{
    /**
     * @var string
     */
    protected $_userId = '0';

    /**
     * @var string
     */
    protected $_userName = '';

    /**
     * @var string
     */
    protected $_roleId = 0;

    /**
     * @var string
     */
    protected $_roleName = '';

    /**
     * UserIdentity constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['userId'])) {
            $this->_userId = $options['userId'];
        }

        if (isset($options['userName'])) {
            $this->_userName = $options['userName'];
        }

        if (isset($options['roleId'])) {
            $this->_roleId = $options['roleId'];
        }

        if (isset($options['roleName'])) {
            $this->_roleName = $options['roleName'];
        }
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->_userId;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_userName;
    }

    /**
     * @return int
     */
    public function getRoleId()
    {
        return $this->_roleId;
    }

    /**
     * @return string
     */
    public function getRoleName()
    {
        return $this->_roleName;
    }
}