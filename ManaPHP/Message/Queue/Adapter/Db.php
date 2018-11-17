<?php
namespace ManaPHP\Message\Queue\Adapter;

use ManaPHP\Message\Queue;

class Db extends Queue
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
     */
    public function do_push($topic, $body, $priority = Queue::PRIORITY_NORMAL)
    {
        /**
         * @var \ManaPHP\Message\Queue\Adapter\Db\Model $model
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
    public function do_pop($topic, $timeout = PHP_INT_MAX)
    {
        /**
         * @var \ManaPHP\Message\Queue\Adapter\Db\Model $model
         * @var \ManaPHP\Message\Queue\Adapter\Db\Model $modelInstance
         */
        $modelInstance = new $this->_model();

        $startTime = time();

        $prev_max = null;
        do {
            $max_id = $modelInstance::max('id');
            if ($prev_max !== $max_id) {
                $prev_max = $max_id;

                $models = $modelInstance::all(['topic' => $topic, 'deleted_time' => 0], ['order' => 'priority ASC, id ASC', 'limit' => 1]);
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
    public function do_delete($topic)
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
     */
    public function do_length($topic, $priority = null)
    {
        /**
         * @var \ManaPHP\Message\Queue\Adapter\Db\Model $model
         */
        $model = new $this->_model();

        if ($priority === null) {
            return $model::count(['topic' => $topic, 'deleted_time' => 0]);
        } else {
            return $model::count(['topic' => $topic, 'deleted_time' => 0, 'priority' => $priority]);
        }
    }
}