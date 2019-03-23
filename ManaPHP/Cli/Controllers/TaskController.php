<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Console;
use ManaPHP\Cli\Controller;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Utility\Text;

/**
 * Class TaskController
 * @package ManaPHP\Cli\Controllers
 *
 * @property-read \ManaPHP\Task\ManagerInterface $tasksManager
 */
class TaskController extends Controller
{
    /**
     * list all tasks
     */
    public function listCommand()
    {
        $this->console->writeLn('tasks list:');

        $tasks = [];
        $tasksDir = $this->alias->resolve('@app/Tasks');
        if (is_dir($tasksDir)) {
            foreach (glob("$tasksDir/*Task.php") as $file) {
                $task = basename($file, 'Task.php');
                $tasks[] = ['name' => Text::underscore($task), 'desc' => $this->_getTaskDescription($task)];
            }
        }
        $width = max(array_map(function ($v) {
                return strlen($v['name']);
            }, $tasks)) + 3;
        foreach ($tasks as $task) {
            $this->console->writeLn(['    :1 :2', $this->console->colorize($task['name'], Console::FC_MAGENTA, $width), $task['desc']]);
        }
    }

    /**
     * @param string $task
     *
     * @return string
     */
    protected function _getTaskDescription($task)
    {
        $className = $this->alias->resolveNS('@ns.app\\Tasks\\' . Text::camelize($task) . 'Task');
        if (!$className) {
            throw new InvalidArgumentException([':task task class is not exists', 'task' => $className]);
        }

        $rm = (new \ReflectionClass($className))->getMethod('run');
        $comment = $rm->getDocComment();
        if (!$comment) {
            return '';
        }

        $lines = preg_split('#[\r\n]+#', $comment);
        return isset($lines[1]) ? trim(trim($lines[1]), '/* ') : '';
    }

    /**
     * run a task
     *
     * @param string $task
     *
     * @return int
     */
    public function runCommand($task = '')
    {
        if (!$task && $tasks = $this->arguments->getValues()) {
            $task = $tasks[0];
        }

        if (!$task) {
            return $this->arguments->getOption('task|t');
        }

        $this->tasksManager->run($task);

        return 0;
    }
}