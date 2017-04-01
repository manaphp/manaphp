<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Cli\Controllers\ModuleController
 *
 * @package ManaPHP\Cli\Controllers
 *
 * @property \ManaPHP\Cli\Application $application
 */
class ModuleController extends Controller
{
    /**
     * @CliCommand list all modules
     */
    public function listCommand()
    {
        $modules = $this->configure->modules;
        ksort($modules);
        foreach ($modules as $module => $bind) {
            $this->console->writeLn($module . ' => ' . $bind);
        }
    }

    /**
     * @CliCommand create a new module
     * @CliParam   --module,-m  the module name
     * @CliParam   --api        skip create view related files
     * @throws \ManaPHP\Filesystem\Adapter\Exception
     * @throws \ManaPHP\Cli\Controllers\Exception
     */
    public function createCommand()
    {
        $module = $this->arguments->get('module:m');
        $api = $this->arguments->has('api');
        if (!$module) {
            $arguments = $this->arguments->get();
            if ($arguments[0] !== '-') {
                $module = $arguments[0];
            } else {
                return $this->console->error('please use --module assign in the module name');
            }
        }

        $module = Text::camelize($module);
        $moduleDir = $this->alias->resolve('@app/' . $module);

        if ($this->filesystem->dirExists($moduleDir)) {
            return $this->console->error('`:module` module is exists already.', ['module' => $module]);
        }

        $this->filesystem->dirCreate($moduleDir . '/Models');
        if (!$api) {
            $this->filesystem->dirCreate($moduleDir . '/Views');
            $this->filesystem->dirCreate($moduleDir . '/Views/Shared');
            $this->filesystem->dirCreate($moduleDir . '/Views/Layouts');
            $this->filesystem->dirCreate($moduleDir . '/Views/Widgets');
            $this->filesystem->dirCreate($moduleDir . '/Widgets');
        }
        $this->filesystem->dirCreate($moduleDir . '/Controllers');

//------------------------------
        $controllerBaseContent = <<<EOD
<?php

namespace Application\\$module\Controllers;

use ManaPHP\Mvc\Controller;

class ControllerBase extends Controller
{

}
EOD;
        $this->filesystem->filePut($moduleDir . './Controllers/ControllerBase.php', $controllerBaseContent);
//---------------------------------------
        $indexControllerContent = <<<EOD
<?php

namespace Application\\$module\Controllers;

class IndexController extends ControllerBase
{
    public function indexAction(){
        echo __FILE__;
    }
}

EOD;
        $this->filesystem->filePut($moduleDir . '/Controllers/IndexController.php', $indexControllerContent);
//-----------------------
        $moduleContent = <<<EOD
<?php
namespace Application\\$module;

class Module extends \ManaPHP\Mvc\Module
{
    public function registerServices(\$di)
    {

    }

    public function authorize(\$controller, \$action)
    {
        return true;
    }
}
EOD;
        $this->filesystem->filePut($moduleDir . '/Module.php', $moduleContent);
//------------------------
        $routeGroupContent = <<<EOD
<?php
namespace Application\\$module;

use ManaPHP\Mvc\Router\Group;

class RouteGroup extends Group
{
    public function __construct()
    {
        parent::__construct(true);
    }
}
EOD;
        $this->filesystem->filePut($moduleDir . '/RouteGroup.php', $routeGroupContent);

        $viewLayoutsDefaultContent = <<<EOD
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>$module</title>
</head>
<body>
@content()	
</body>
</html>
EOD;
        if (!$api) {
            $this->filesystem->filePut($moduleDir . '/Views/Layouts/Default.sword', $viewLayoutsDefaultContent);
            $this->filesystem->filePut($moduleDir . '/Views/Layouts/Index.sword', $viewLayoutsDefaultContent);
//----------
            $viewIndexContent = <<<EOD
        
    <h1>$module</h1>
EOD;
            $this->filesystem->filePut($moduleDir . '/Views/Index/Index.sword', $viewIndexContent);
        }
    }
}