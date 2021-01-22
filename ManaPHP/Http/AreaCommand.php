<?php

namespace ManaPHP\Http;

use ManaPHP\Cli\Command;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;

class AreaCommand extends Command
{
    /**
     * create area directory tree
     *
     * @param string $area
     */
    public function createAction($area = '')
    {
        if ($area === '' && !$area = $this->request->getValue(0)) {
            return $this->console->error('area name is not provided');
        }

        $area = Str::camelize($area);

        $dir = $this->alias->resolve("@app/Areas/$area");
        if (LocalFS::dirExists($dir)) {
            return $this->console->error("$area is exists already");
        }

        LocalFS::dirCreate("$dir/Controllers");
        LocalFS::dirCreate("$dir/Views");
        LocalFS::dirCreate("$dir/Models");
        LocalFS::dirCreate("$dir/Services");

        $controller = <<<EOT
<?php

namespace App\Areas\{area}\Controllers;

use App\Controllers\Controller;

class IndexController extends Controller
{
    public function indexAction()
    {
        return 0;
    }
}
EOT;
        LocalFS::filePut("$dir/Controllers/IndexController.php", strtr($controller, ['{area}' => $area]));
    }

    /**
     * list all areas
     */
    public function listAction()
    {
        $areas = [];
        foreach (LocalFS::glob('@app/Areas/*') as $item) {
            $areas[] = basename($item);
        }

        $this->console->writeLn(json_stringify($areas));
    }
}