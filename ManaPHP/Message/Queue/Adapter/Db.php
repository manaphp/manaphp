<?php
namespace ManaPHP\Message\Queue\Adapter;

use ManaPHP\Component;
use ManaPHP\Message\QueueInterface;

class Db extends Component implements QueueInterface
{
    /**
     * @var string
     */
    protected $_model = 'ManaPHP\Message\Queue\Adapter\Db\Model';

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
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function push($topic, $body, $priority = self::PRIORITY_NORMAL)
    {
        /**
         * @var \ManaPHP\Message\Queue\Adapter\Db\Model $model
         */
        $model = new $this->_model();

        $model->deleted = 0;
        $model->topic = $topic;
        $model->body = $body;
        $model->priority = $priority;
        $model->created_time = time();

        $model->create();
    }

    /**
     * @param string $topic
     * @param int    $timeout
     *
     * @return string|false
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function pop($topic, $timeout = PHP_INT_MAX)
    {
        /**
         * @var \ManaPHP\Message\Queue\Adapter\Db\Model $model
         * @var \ManaPHP\Message\Queue\Adapter\Db\Model $modelInstance
         */
        $modelInstance = new $this->_model();

        $startTime = time();

        do {
            $model = $modelInstance::findFirst([['topic' => $topic, 'deleted' => 0], 'order' => 'priority ASC, id ASC']);
            if ($model && $modelInstance::updateAll(['deleted' => 1], ['id' => $model->id, 'deleted = 0'])) {
                return $model->body;
            }

            sleep(1);
        } while (time() - $startTime < $timeout);

        return false;
    }

    /**
     * @param string $topic
     *
     * @return void
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function delete($topic)
    {
        /**
         * @var \ManaPHP\Message\Queue\Adapter\Db\Model $model
         */
        $model = new $this->_model();

        $model::deleteAll(['topic' => $topic]);
    }

    /**
     * @param string $topic
     * @param int    $priority
     *
     * @return int
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function length($topic, $priority = null)
    {
        /**
         * @var \ManaPHP\Message\Queue\Adapter\Db\Model $model
         */
        $model = new $this->_model();

        if ($priority === null) {
            return $model::count(['topic' => $topic, 'deleted' => 0]);
        } else {
            return $model::count(['topic' => $topic, 'deleted' => 0, 'priority' => $priority]);
        }
    }
}