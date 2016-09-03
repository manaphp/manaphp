<?php
namespace ManaPHP\Http\Session\Adapter;

use ManaPHP\Component;
use ManaPHP\Http\Session\AdapterInterface;

class Db extends Component implements AdapterInterface
{

    protected $_ttl;

    protected $_model = 'ManaPHP\Http\Session\Adapter\Db\Model';

    /**
     * Db constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (is_object($options)) {
            $options = (array)$options;
        }

        $this->_ttl = (int)(isset($options['ttl']) ? $options['ttl'] : ini_get('session.gc_maxlifetime'));

        if (isset($options['model'])) {
            $this->_model = $options['model'];
        }
    }

    /**
     * @param string $savePath
     * @param string $sessionName
     *
     * @return bool
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * @param string $sessionId
     *
     * @return string
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function read($sessionId)
    {
        /**
         * @var \ManaPHP\Http\Session\Adapter\Db\Model $model ;
         */
        $model = new $this->_model;
        $model = $model::findFirst(['session_id' => $sessionId]);
        if ($model !== false && $model->expired_time > time()) {
            return $model->data;
        } else {
            return '';
        }
    }

    /**
     * @param string $sessionId
     * @param string $data
     *
     * @return bool
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function write($sessionId, $data)
    {
        /**
         * @var \ManaPHP\Http\Session\Adapter\Db\Model $model ;
         */
        $model = new $this->_model;

        $model->session_id = $sessionId;
        $model->data = $data;
        $model->expired_time = time() + $this->_ttl;
        $model->save();

        return true;
    }

    /**
     * @param string $sessionId
     *
     * @return bool
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function destroy($sessionId)
    {
        /**
         * @var \ManaPHP\Http\Session\Adapter\Db\Model $model ;
         */
        $model = new $this->_model;

        $model::deleteAll(['session_id' => $sessionId]);

        return true;
    }

    /**
     * @param int $ttl
     *
     * @return bool
     */
    public function gc($ttl)
    {
        return true;
    }
}