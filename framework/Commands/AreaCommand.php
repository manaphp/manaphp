<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\AliasInterface;
use ManaPHP\Cli\Command;
use ManaPHP\Cli\OptionsInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;

class AreaCommand extends Command
{
    #[Autowired] protected AliasInterface $alias;
    #[Autowired] protected OptionsInterface $options;

    /**
     * create area directory tree
     *
     * @param string $area
     *
     * @return int
     */
    public function createAction(string $area = ''): int
    {
        if ($area === '') {
            if (($v = $this->options->get('')) === null) {
                return $this->console->error('area name is not provided');
            } else {
                $area = (string)$v;
            }
        }

        $area = Str::pascalize($area);

        $dir = $this->alias->resolve("@app/Areas/$area");
        if (LocalFS::dirExists($dir)) {
            return $this->console->error("`$area` is exists already");
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

        return 0;
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