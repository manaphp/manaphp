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
     * @param \ManaPHP\Logger\Log $log
     *
     * @return void
     * @throws \ManaPHP\Model\Exception
     */
    public function append($log)
    {
        if ($this->_nested) {
            return;
        }

        $this->_nested = true;

        /**
         * @var \ManaPHP\Logger\Appender\Db\Model $logModel
         */
        $logModel = new $this->_model;

        $logModel->user_id = $this->userIdentity->getId();
        $logModel->user_name = $this->userIdentity->getName();
        $logModel->level = $log->level;
        $logModel->category = $log->category;
        $logModel->location = $log->location;
        $logModel->caller = $log->caller;
        $logModel->message = $log->message;
        $logModel->created_time = $log->timestamp;
        $logModel->create();

        $this->_nested = false;
    }
}