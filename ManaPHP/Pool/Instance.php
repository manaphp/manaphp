<?php
namespace ManaPHP\Pool;

use ManaPHP\Component;

class Instance extends Component
{
    protected $_owner;
    protected $_target;

    public function __construct($owner, $target)
    {
        $this->_owner = $owner;
        $this->_target = $target;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->_target, $name], $arguments);
    }

    public function __destruct()
    {
        if ($this->_owner) {
            $this->poolManager->pop($this->_owner, $this->_target);
        }
    }
}