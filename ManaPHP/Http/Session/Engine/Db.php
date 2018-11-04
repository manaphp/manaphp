<?php
namespace ManaPHP\Http\Session\Engine;

use ManaPHP\Component;
use ManaPHP\Http\Session\EngineInterface;

/**
 * Class ManaPHP\Http\Session\Engine\Db
 *
 * @package session\engine
 * @property-read \ManaPHP\Http\RequestInterface $request
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
        if (isset($options['model'])) {
            $this->_model = $options['model'];
        }
    }

    /**
     * @param string $session_id
     *
     * @return string
     */
    public function read($session_id)
    {
        /**
         * @var \ManaPHP\Http\Session\Engine\Db\Model $model
         */
        $model = new $this->_model;
        $model = $model::first(['session_id' => $session_id]);
        if ($model && $model->expired_time > time()) {
            return $model->data;
        } else {
            return '';
        }
    }

    /**
     * @param string $session_id
     * @param string $data
     * @param int    $ttl
     *
     * @return bool
     */
    public function write($session_id, $data, $ttl)
    {
        /**
         * @var \ManaPHP\Http\Session\Engine\Db\Model $model
         */
        $model = new $this->_model;

        $model->session_id = $session_id;
        $model->user_id = $this->identity->getId(0);
        $model->client_ip = $this->request->getClientIp();
        $model->data = $data;
        $model->updated_time = time();
        $model->expired_time = $model->updated_time + $ttl;

        $model->save();

        return true;
    }

    /**
     * @param string $session_id
     *
     * @return bool
     */
    public function destroy($session_id)
    {
        /**
         * @var \ManaPHP\Http\Session\Engine\Db\Model $model
         */
        $model = new $this->_model;

        $model::deleteAll(['session_id' => $session_id]);

        return true;
    }

    /**
     * @param int $ttl
     *
     * @return bool
     */
    public function gc($ttl)
    {
        /**
         * @var \ManaPHP\Http\Session\Engine\Db\Model $model
         */
        $model = new $this->_model;

        $model::deleteAll(['expired_time<=' => time()]);

        return true;
    }
}