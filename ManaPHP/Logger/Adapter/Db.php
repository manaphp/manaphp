<?php
namespace ManaPHP\Logger\Adapter;

use ManaPHP\Component;
use ManaPHP\Logger\AdapterInterface;

/**
 * Class Db
 *
 * @package ManaPHP\Logger\Adapter
 * @property \ManaPHP\Authentication\UserIdentityInterface $userIdentity
 * @property \ManaPHP\Mvc\DispatcherInterface              $dispatcher
 * @property \ManaPHP\Http\RequestInterface                $request
 */
class Db extends Component implements AdapterInterface
{
    /**
     * @var string
     */
    protected $_model = '\ManaPHP\Logger\Adapter\Db\Model';

    /**
     * @var \ManaPHP\Logger\Adapter\Db\Model
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
     * @param string $level
     * @param string $message
     * @param array  $context
     *
     * @return void
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function log($level, $message, $context = [])
    {
        if ($this->_nested) {
            return;
        }

        $this->_nested = true;
        if ($this->_log === null) {
            /**
             * @var \ManaPHP\Logger\Adapter\Db\Model $log
             */
            $log = new $this->_model;
            $log->level = $level;
            $log->user_id = $this->userIdentity->getId();
            $log->user_name = $this->userIdentity->getName();
            $log->module = $this->dispatcher->getModuleName();
            $log->controller = $this->dispatcher->getControllerName();
            $log->action = $this->dispatcher->getActionName();
            $log->ip = $this->request->getClientAddress();
            $this->_log = $log;
        }

        $this->_log->log_id = null;
        $this->_log->message = $message;
        $this->_log->created_time = time();

        $this->_log->create();

        $this->_nested = false;
    }
}