<?php

namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Cli\Controllers\ControllerController
 *
 * @package ManaPHP\Cli\Controllers
 *
 * @property \ManaPHP\ApplicationInterface $application
 * @property \ManaPHP\RendererInterface    $renderer
 */
class ControllerController extends Controller
{
    /**
     * create controller
     *
     * @param string $module     the module for controller
     * @param string $controller the controller name
     * @param array  $actions    the action list for controller
     * @param int    $force      force to recreate all files
     * @param int    $api        skip all views
     *
     * @return int
     */
    public function createCommand($module = 'Home', $controller, $actions = ['list', 'create', 'detail', 'update', 'delete'], $force = 0, $api = 0)
    {
        $moduleName = Text::camelize($this->crossword->guess($this->application->getModules(), $module));
        if (!$moduleName) {
            return $this->console->error(['module name is unknown: `:module`', 'module' => $module]);
        }
        $controller = Text::camelize($controller);
        $controllerName = $controller . 'Controller';
        $controllerFile = '@app/' . $moduleName . '/Controllers/' . $controllerName . '.php';

        $controllerNamespace = $this->alias->resolveNS('@ns.app' . '\\' . $moduleName . '\\Controllers');

        if (!$force && $this->filesystem->fileExists($controllerFile)) {
            return $this->console->error(['`:controller` controller exists already', 'controller' => $controllerNamespace . '\\' . $controller]);
        }

        foreach ($actions as $i => $action) {
            $action = trim($action);
            $action = lcfirst(Text::camelize($action));

            $actions[$i] = $action;
        }

        $controllerBase = !$this->filesystem->fileExists("@app/$moduleName/Controllers/ControllerBase.php") ? 'ControllerBase' : '\ManaPHP\Mvc\Controller';

        $vars = compact('controllerNamespace', 'controllerName', 'actions', 'controllerBase');

        $this->filesystem->filePut($controllerFile, $this->renderer->render('@manaphp/Cli/Controllers/Templates/Controller', $vars));

        if (!$api) {
            $this->filesystem->filePut("@app/$moduleName/Views/Layouts/$controller.sword", '@content()');

            foreach ($actions as $action) {
                $action = Text::camelize($action);
                $this->filesystem->filePut("@app/$moduleName/Views/$controller/$action.sword", '');
            }
        }

        return 0;
    }
}