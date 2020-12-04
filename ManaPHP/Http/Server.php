<?php

namespace ManaPHP\Http;

use ManaPHP\Aop\Unaspectable;
use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Http\RequestInterface         $request
 * @property-read \ManaPHP\Http\Globals\ManagerInterface $globalsManager
 */
abstract class Server extends Component implements ServerInterface, Unaspectable
{
    /**
     * @var bool
     */
    protected $_use_globals = false;

    /**
     * @var string
     */
    protected $_host = '0.0.0.0';

    /**
     * @var int
     */
    protected $_port = 9501;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['use_globals'])) {
            $this->_use_globals = (bool)$options['use_globals'];
        }

        if (isset($options['max_request']) && $options['max_request'] < 1) {
            $options['max_request'] = 1;
        }

        if (isset($options['host'])) {
            $this->_host = $options['host'];
        }

        if (isset($options['port'])) {
            $this->_port = (int)$options['port'];
        }
    }

    public function log($level, $message)
    {
        echo sprintf('[%s][%s]: ', date('c'), $level), $message, PHP_EOL;
    }

    protected function _releaseContexts()
    {
        global $__root_context;

        if ($__root_context !== null) {
            foreach ($__root_context as $owner) {
                unset($owner->_context);
            }

            $__root_context = null;
        }
    }
}