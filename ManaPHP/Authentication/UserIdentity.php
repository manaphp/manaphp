<?php
namespace ManaPHP\Authentication;

class UserIdentity implements UserIdentityInterface
{
    /**
     * @var int
     */
    protected $_userId = 0;

    /**
     * @var string
     */
    protected $_userName = '';

    public function __construct($id, $name = null)
    {
        $this->_userId = $id;
        $this->_userName = $name !== null ? $name : $id;
    }

    public function getId()
    {
        return $this->_userId;
    }

    public function getName()
    {
        return $this->_userName;
    }
}