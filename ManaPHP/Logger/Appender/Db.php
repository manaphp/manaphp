<?php

namespace ManaPHP\Logger\Appender;

use ManaPHP\Component;
use ManaPHP\Logger\AppenderInterface;

/**
 * Class ManaPHP\Logger\Appender\Db
 *
 * @package logger
 *
 * @property \ManaPHP\Authentication\UserIdentityInterface $userIdentity
 * @property \ManaPHP\Mvc\DispatcherInterface              $dispatcher
 * @property \ManaPHP\Http\RequestInterface                $request
 */
class Db extends Component implements AppenderInterface
{
    /**
     * @var string
     */
    protected $_model = '\ManaPHP\Logger\Appender\Db\Model';

    /**
     * @var \ManaPHP\Logger\Appender\Db\Model
     */
    protected $_log;

    /**
     * @var bool
     */
    protected $_nested = false;

    /**
     * Db constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_string($options)) {
            $options = ['model' => $options];
        }

        if (isset($options['model'])) {
            $this->_model = $options['model'];
        }
    }

    /**
     * @param array $logEvent
     *
     * @return void
     * @throws \ManaPHP\Model\Exception
     * @throws \ManaPHP\Db\Model\Exception
     */
    public function append($logEvent)
    {
        if ($this->_nested) {
            return;
        }

        $this->_nested = true;
        if ($this->_log === null) {
            /**
             * @var \ManaPHP\Logger\Appender\Db\Model $log
             */
            $log = new $this->_model;

            $log->user_id = $this->userIdentity->getId();
            $log->user_name = $this->userIdentity->getName();
            $log->ip = $this->request->getClientAddress();
            $this->_log = $log;
        }

        $this->_log->log_id = null;
        $log->level = $logEvent['level'];
        $this->_log->category = $logEvent['category'];
        $this->_log->location = $logEvent['location'];
        $this->_log->message = $logEvent['message'];
        $this->_log->created_time = $logEvent['timestamp'];

        $this->_log->create();

        $this->_nested = false;
    }
}