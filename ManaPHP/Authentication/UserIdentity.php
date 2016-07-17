<?php
namespace ManaPHP\Authentication;

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
     * UserIdentity constructor.
     *
     * @param int|string $id
     * @param string     $name
     */
    public function __construct($id, $name = '')
    {
        $this->_userId = (string)$id;
        $this->_userName = $name ?: $id;
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