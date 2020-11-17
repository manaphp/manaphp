<?php

namespace ManaPHP\Event;

use ManaPHP\Component;

class Tracer extends Component
{
    /**
     * @var bool
     */
    protected $_verbose = false;

    public function __construct($options = [])
    {
        if (isset($options['verbose'])) {
            $this->_verbose = (bool)$options['verbose'];
        }
    }
}