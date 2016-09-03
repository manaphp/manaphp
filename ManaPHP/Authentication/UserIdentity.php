<?php
namespace ManaPHP\Authentication;

class UserIdentity implements UserIdentityInterface
{
    /**
     * @var string
     */
    protected $_userId;

    /**
     * @var string
     */
    protected $_userName;

    /**
     * UserIdentity constructor.
     *
     * @param string $id
     * @param string $name
     */
    public function __construct($id = '0', $name = '')
    {
        $this->_userId = $id;
        $this->_userName = $name;
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
}