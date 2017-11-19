<?php
namespace ManaPHP\Http\Session\Engine;

use ManaPHP\Component;
use ManaPHP\Http\Session\EngineInterface;

/**
 * Class ManaPHP\Http\Session\Engine\Db
 *
 * @package session\engine
 */
class Db extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $_model = 'ManaPHP\Http\Session\Engine\Db\Model';

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
     * @throws \ManaPHP\Model\Exception
     */
    public function read($sessionId)
    {
        /**
         * @var \ManaPHP\Http\Session\Engine\Db\Model $model
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
     * @param int    $ttl
     *
     * @return bool
     * @throws \ManaPHP\Model\Exception
     */
    public function write($sessionId, $data, $ttl)
    {
        /**
         * @var \ManaPHP\Http\Session\Engine\Db\Model $model
         */
        $model = new $this->_model;

        $model->session_id = $sessionId;
        $model->data = $data;
        $model->ttl = $ttl;
        $model->expired_time = time() + $ttl;

        $model->save();

        return true;
    }

    /**
     * @param string $sessionId
     *
     * @return bool
     * @throws \ManaPHP\Model\Exception
     */
    public function destroy($sessionId)
    {
        /**
         * @var \ManaPHP\Http\Session\Engine\Db\Model $model
         */
        $model = new $this->_model;

        $model::deleteAll(['session_id' => $sessionId]);

        return true;
    }

    /**
     * @param int $ttl
     *
     * @return bool
     * @throws \ManaPHP\Model\Exception
     */
    public function gc($ttl)
    {
        $this->clean();

        return true;
    }

    /**
     * @return void
     * @throws \ManaPHP\Model\Exception
     */
    public function clean()
    {
        /**
         * @var \ManaPHP\Http\Session\Engine\Db\Model $model
         */
        $model = new $this->_model;

        $model::deleteAll(['expired_time<=' => time()]);
    }
}