<?php
namespace ManaPHP\Message\Queue\Engine;

use ManaPHP\Component;
use ManaPHP\Message\Queue;
use ManaPHP\Message\Queue\EngineInterface;

/**
 * Class ManaPHP\Message\Queue\Engine\Db
 *
 * @package messageQueue\engine
 */
class Db extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $_model = 'ManaPHP\Message\Queue\Engine\Db\Model';

    /**
     *
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
     * @param string $topic
     * @param string $body
     * @param int    $priority
     *
     * @return void
     * @throws \ManaPHP\Model\Exception
     */
    public function push($topic, $body, $priority = Queue::PRIORITY_NORMAL)
    {
        /**
         * @var \ManaPHP\Message\Queue\Engine\Db\Model $model
         */
        $model = new $this->_model();

        $model->priority = $priority;
        $model->topic = $topic;
        $model->body = $body;
        $model->created_time = time();
        $model->deleted_time = 0;

        $model->create();
    }

    /**
     * @param string $topic
     * @param int    $timeout
     *
     * @return string|false
     */
    public function pop($topic, $timeout = PHP_INT_MAX)
    {
        /**
         * @var \ManaPHP\Message\Queue\Engine\Db\Model $model
         * @var \ManaPHP\Message\Queue\Engine\Db\Model $modelInstance
         */
        $modelInstance = new $this->_model();

        $startTime = time();

        $prev_max = null;
        do {
            $max_id = $modelInstance::max('id');
            if ($prev_max !== $max_id) {
                $prev_max = $max_id;

                $models = $modelInstance::find(['topic' => $topic, 'deleted_time' => 0], ['order' => 'priority ASC, id ASC', 'limit' => 1]);
                $model = isset($models[0]) ? $models[0] : false;

                if ($model && $modelInstance::updateAll(['deleted_time' => time()], ['id' => $model->id])) {
                    return $model->body;
                }
            }
            sleep(1);
        } while (time() - $startTime < $timeout);

        return false;
    }

    /**
     * @param string $topic
     *
     * @return void
     */
    public function delete($topic)
    {
        /**
         * @var \ManaPHP\Message\Queue\Engine\Db\Model $model
         */
        $model = new $this->_model();

        $model::deleteAll(['topic' => $topic]);
    }

    /**
     * @param string $topic
     * @param int    $priority
     *
     * @return int
     */
    public function length($topic, $priority = null)
    {
        /**
         * @var \ManaPHP\Message\Queue\Engine\Db\Model $model
         */
        $model = new $this->_model();

        if ($priority === null) {
            return $model::count(['topic' => $topic, 'deleted_time' => 0]);
        } else {
            return $model::count(['topic' => $topic, 'deleted_time' => 0, 'priority' => $priority]);
        }
    }
}