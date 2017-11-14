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
 */
class Db extends Component implements AppenderInterface
{
    /**
     * @var string
     */
    protected $_model = '\ManaPHP\Logger\Appender\Db\Model';

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

        /**
         * @var \ManaPHP\Logger\Appender\Db\Model $log
         */
        $log = new $this->_model;

        $log->user_id = $this->userIdentity->getId();
        $log->user_name = $this->userIdentity->getName();
        $log->level = $logEvent['level'];
        $log->category = $logEvent['category'];
        $log->location = $logEvent['location'];
        $log->caller = $logEvent['caller'];
        $log->message = $logEvent['message'];
        $log->client_ip = $logEvent['client_ip'];
        $log->created_time = $logEvent['timestamp'];

        $log->create();

        $this->_nested = false;
    }
}