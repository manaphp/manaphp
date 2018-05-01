<?php
namespace ManaPHP\Task;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Utility\Text;

class Manager extends Component implements ManagerInterface
{
    /**
     * @var int
     */
    protected $_errorDelay = 1;

    /**
     * Manager constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['errorDelay'])) {
            $this->_errorDelay = $options['errorDelay'];
        }
    }

    /**
     * @param string $task
     */
    public function run($task)
    {
        if (strpos($task, '\\') === false) {
            $task = Text::underscore($task);
            $className = $this->alias->resolveNS('@ns.app\\Tasks\\' . Text::camelize($task) . 'Task');
        } else {
            $className = $task;
            $task = Text::underscore(basename($task, 'Task'));
        }

        if (!class_exists($className)) {
            throw new InvalidArgumentException('task class is not exists: :task => :class', ['task' => $task, 'class' => $className]);
        }

        while (true) {
            try {
                /**
                 * @var \ManaPHP\TaskInterface $instance
                 */
                $this->logger->info(['`:name`(:class) starting...', 'name' => $task, 'class' => $className]);
                $instance = new $className();
                $this->logger->info(['`:name` start successfully', 'name' => $task]);
                while (true) {
                    $instance->run();
                    sleep($instance->getInterval());
                }
            } catch (\Exception $e) {
                $this->logger->error($e);
                sleep($instance->getErrorDelay() ?: $this->_errorDelay);
            }
        }

        $this->logger->info(['`:name` stop successfully: :name => :class', 'name' => $task, 'class' => $className]);
    }
}