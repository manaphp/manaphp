<?php
namespace Application\Admin\Controllers;

use ManaPHP\Task;
use ManaPHP\Task\Metadata;

/**
 * Class TaskController
 *
 * @package Application\Admin\Controllers
 *
 * @property \ManaPHP\Task\Metadata $tasksMetadata
 */
class TaskController extends ControllerBase
{
    /**
     * @var array
     */
    protected $_tasks = [];

    public function onConstruct()
    {
        foreach (glob($this->alias->get('@app') . '/*', GLOB_ONLYDIR) as $entry) {

            $files = glob($entry . '/Tasks/*Task.php');
            foreach ($files as $file) {
                $file = str_replace('\\', '/', $file);

                if (preg_match('#([^/]*/([^/]*)/Tasks/(.*)Task)\.php$#i', $file, $match) === 1) {
                    $this->_tasks[$match[2] . ':' . $match[3]] = str_replace('/', '\\', $match[1]);
                }
            }
        }
    }

    public function indexAction()
    {
        $tasks = [];
        foreach ($this->_tasks as $taskId => $taskName) {
            $tasks[$taskId] = $this->tasksMetadata->getAll($taskName);
        }

        ksort($tasks);

        $data = [];

        $data['tasks'] = $tasks;

        $this->view->setVar('data', $data);
    }

    public function startAction()
    {
        $taskId = $this->request->get('task', 'ignore');

        if (isset($this->_tasks[$taskId])) {
            $taskName = $this->_tasks[$taskId];

            /** @noinspection TypeUnsafeComparisonInspection */
            if ($this->tasksMetadata->get($taskName, Metadata::FIELD_STATUS) != Task::STATUS_RUNNING) {
                $this->tasksMetadata->reset($taskName);

                $this->response->redirect('/admin/task');
                /**
                 * @var $task \ManaPHP\Task
                 */
                $task = new $taskName();

                $task->start();
            }
        }

        return $this->response->redirect('/admin/task');
    }

    public function stopAction()
    {
        $taskId = $this->request->get('task', 'ignore');

        if (isset($this->_tasks[$taskId])) {
            $taskName = $this->_tasks[$taskId];

            $this->tasksMetadata->set($taskName, Metadata::FIELD_CANCEL_FLAG, 1);
        }

        return $this->response->redirect('/admin/task');
    }

    public function killAction()
    {
        $taskId = $this->request->get('task', 'ignore');

        if (isset($this->_tasks[$taskId])) {
            $taskName = $this->_tasks[$taskId];

            if ($this->tasksMetadata->exists($taskName, Metadata::FIELD_STATUS)) {
                $this->tasksMetadata->set($taskName, Metadata::FIELD_CANCEL_FLAG, 1);

                for ($i = 0; $i < 2; $i++) {
                    sleep(1);
                    if ($this->tasksMetadata->get($taskName, Metadata::FIELD_STATUS) == Task::STATUS_STOP) {
                        break;
                    }
                }
            }

            $this->tasksMetadata->reset($taskName);
        }

        return $this->response->redirect('/admin/task');
    }
}