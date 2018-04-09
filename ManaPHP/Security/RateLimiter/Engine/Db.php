<?php
namespace ManaPHP\Security\RateLimiter\Engine;

use ManaPHP\Component;
use ManaPHP\Security\RateLimiter\EngineInterface;

/**
 * Class ManaPHP\Security\RateLimiter\Engine\Db
 *
 * @package rateLimiter\engine
 */
class Db extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $_model = 'ManaPHP\Security\RateLimiter\Engine\Db\Model';

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
     * @param string $type
     * @param string $id
     * @param int    $duration
     *
     * @return int
     */
    public function check($type, $id, $duration)
    {
        /**
         * @var \ManaPHP\Security\RateLimiter\Engine\Db\Model $model
         * @var \ManaPHP\Security\RateLimiter\Engine\Db\Model $rateLimiter
         */
        $model = new $this->_model();
        $hash = md5($type . ':' . $id);
        $rateLimiter = $model::findFirst(['hash' => $hash]);
        if (!$rateLimiter) {
            $rateLimiter = new $this->_model();

            $rateLimiter->hash = $hash;
            $rateLimiter->type = $type;
            $rateLimiter->id = $id;
            $rateLimiter->times = 1;
            $rateLimiter->expired_time = time() + $duration;
        } else {
            if (time() > $rateLimiter->expired_time) {
                $rateLimiter->expired_time = time() + $duration;
                $rateLimiter->times = 1;
            } else {
                $rateLimiter->times++;
            }
        }
        $rateLimiter->save();

        return $rateLimiter->times;
    }
}