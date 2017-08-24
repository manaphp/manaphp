<?php
namespace ManaPHP\Http\Session\Adapter;

use ManaPHP\Component;
use ManaPHP\Http\Session\AdapterInterface;

/**
 * Class ManaPHP\Http\Session\Adapter\Db
 *
 * @package session\adapter
 */
class Db extends Component implements AdapterInterface
{
    /**
     * @var string
     */
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
     * @throws \ManaPHP\Db\Query\Exception
     * @throws \ManaPHP\Db\Model\Exception
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
     * @throws \ManaPHP\Db\Model\Exception
     */
    public function write($sessionId, $data)
    {
        /**
         * @var \ManaPHP\Http\Session\Adapter\Db\Model $model ;
         */
        $model = new $this->_model;

        $model->session_id = $sessionId;
        $model->data = $data;
        $model->expired_time = time() + ini_get('session.gc_maxlifetime');
        $model->save();

        return true;
    }

    /**
     * @param string $sessionId
     *
     * @return bool
     * @throws \ManaPHP\Db\Model\Exception
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
     * @throws \ManaPHP\Db\Model\Exception
     */
    public function gc($ttl)
    {
        $this->clean();

        return true;
    }

    /**
     * @return void
     * @throws \ManaPHP\Db\Model\Exception
     */
    public function clean()
    {
        /**
         * @var \ManaPHP\Http\Session\Adapter\Db\Model $model ;
         */
        $model = new $this->_model;

        $model::deleteAll(['expired_time<=' => time()]);
    }
}