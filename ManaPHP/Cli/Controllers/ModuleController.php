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
     * @description list the modules
     */
    public function listCommand()
    {
        $modules = $this->application->getModules();
        sort($modules);

        $this->console->write(implode(',', $modules));
    }

    /**
     * @description create a new module
     * @throws \Application\Exception
     * @throws \ManaPHP\Filesystem\Adapter\Exception
     */
    public function createCommand()
    {
        $modules = ['dd'];

        foreach ($modules as $module) {
            $module = Text::camelize($module);
            $moduleDir = $this->alias->resolve('@app/' . $module);

            if ($this->filesystem->dirExists($moduleDir)) {
                throw new Exception('`:module` module is exists already.', ['module' => $module]);
            }

            $this->filesystem->dirCreate($moduleDir . '/Models');
            $this->filesystem->dirCreate($moduleDir . '/Views');
            $this->filesystem->dirCreate($moduleDir . '/Views/Shared');
            $this->filesystem->dirCreate($moduleDir . '/Views/Layouts');
            $this->filesystem->dirCreate($moduleDir . '/Views/Widgets');
            $this->filesystem->dirCreate($moduleDir . '/Widgets');
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