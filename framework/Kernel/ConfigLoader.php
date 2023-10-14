<?php
declare(strict_types=1);

namespace ManaPHP\Kernel;

use ManaPHP\AliasInterface;
use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Autowired;

class ConfigLoader implements ConfigLoaderInterface
{
    #[Autowired] protected AliasInterface $alias;
    #[Autowired] protected ConfigInterface $config;

    public function load(): void
    {
        $config = [];

        $dependencies = [];
        $config_dir = $this->alias->get('@config');
        foreach (glob("$config_dir/*.php") as $item) {
            $file = pathinfo($item, PATHINFO_BASENAME);

            if ($file === 'app.php') {
                foreach (require $item as $key => $value) {
                    $this->config->set($key, $value);
                }
            } else {
                $dependencies += require $item;
            }
        }

        $this->config->set('dependencies', $dependencies);
    }
}