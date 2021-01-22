<?php

namespace ManaPHP\Ws\Pushing;

class User
{
    /**
     * @var int
     */
    public $fd;

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $role;

    public function __construct($fd, $id, $name, $role)
    {
        $this->fd = $fd;
        $this->id = $id;
        $this->name = $name;
        $this->role = $role;
    }
}