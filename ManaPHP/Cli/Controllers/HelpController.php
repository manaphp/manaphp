<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;

class HelpController extends Controller
{
    public function defaultAction()
    {
        return $this->listAction();
    }

    public function listAction()
    {
        $controllerNames = [];
        $commands = [];

        foreach ($this->filesystem->glob('@app/Cli/Controllers/*Controller.php') as $file) {
            if (preg_match('#/(\w+/\w+/Controllers/(\w+)Controller)\.php$#', $file, $matches)) {
                $controllerClassName = str_replace('/', '\\', $matches[1]);
                $controllerName = $matches[2];
                $controllerNames[] = $controllerName;

                $rc = new \ReflectionClass($controllerClassName);
                foreach ($rc->getMethods() as $rm) {
                    if (preg_match('#^(.*)Action$#', $rm->getName(), $matches) === 1) {
                        $commands[] = lcfirst($controllerName) . ($matches[1] === 'default' ? '' : (':' . $matches[1]));
                    }
                }
            }
        }

        foreach ($this->filesystem->glob('@manaphp/Cli/Controllers/*Controller.php') as $file) {
            if (preg_match('#/(\w+/\w+/Controllers/(\w+)Controller)\.php$#', $file, $matches)) {
                $controllerClassName = str_replace('/', '\\', $matches[1]);
                $controllerName = $matches[2];
                if (in_array($controllerName, $controllerNames, true)) {
                    continue;
                }

                $rc = new \ReflectionClass($controllerClassName);
                foreach ($rc->getMethods() as $rm) {
                    if (preg_match('#^(.*)Action$#', $rm->getName(), $matches) === 1) {
                        $commands[] = lcfirst($controllerName) . ($matches[1] === 'default' ? '' : (':' . $matches[1]));
                    }
                }
            }
        }

        sort($commands);

        foreach ($commands as $command) {
            $this->console->write($command . " ");
        }
    }
}