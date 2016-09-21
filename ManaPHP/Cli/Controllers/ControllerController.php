<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;
use ManaPHP\Utility\Text;

/**
 * Class ControllerController
 *
 * @package ManaPHP\Cli\Controllers
 * @property \ManaPHP\ApplicationInterface $application
 */
class ControllerController extends Controller
{
    public function createCommand()
    {
        $usage = 'format is invalid: {Module}:{A,B,C,D,E}';

        $arguments = $this->arguments->get();
        if (count($arguments) === 0) {
            $this->console->writeLn($usage);
            return 1;
        }

        $parts = explode(':', $arguments[0]);
        if (count($parts) !== 2) {
            $this->console->writeLn($usage);
            return 1;
        }

        $moduleName = Text::camelize($this->crossword->guess($this->application->getModules(), $parts[0]));
        if (!$moduleName) {
            return $this->console->error('module name is unknown: `:module`', ['module' => $parts[0]]);
        }

        $controllers = explode(',', $parts[1]);
        foreach ($controllers as $controller) {
            $controller = Text::camelize($controller);
            $controllerName = $controller . 'Controller';
            $controllerFile = '@app/' . $moduleName . '/Controllers/' . $controllerName . '.php';
            $controllerNamespace = basename($this->alias->get('@app')) . '\\' . $moduleName . '\\Controllers';

            if ($this->filesystem->fileExists($controllerFile)) {
                $this->console->writeLn('`:controller` controller exists already', ['controller' => $controllerNamespace . '\\' . $controller]);
                continue;
            }
            $controllerContent = <<<EOD
<?php
namespace $controllerNamespace;

class $controllerName extends ControllerBase{
     public function indexAction()
     {
        
     }
}
EOD;
            $this->filesystem->filePut($controllerFile, $controllerContent);
            $this->filesystem->filePut('@app/' . $moduleName . '/Views/' . $controller . '/Index.sword', '');
            $this->filesystem->filePut('@app/' . $moduleName . '/Layouts/' . $controller . '.sword', '@content()');
        }
    }
}