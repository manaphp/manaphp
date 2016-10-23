<?php
namespace ManaPHP;

/**
 * Class ManaPHP\Application
 *
 * @package ManaPHP
 *
 * @property \ManaPHP\Loader            $loader
 * @property \ManaPHP\DebuggerInterface $debugger
 */
abstract class Application extends Component implements ApplicationInterface
{
    /**
     * @var array
     */
    protected $_modules;

    /**
     * @return array
     */
    public function getModules()
    {
        if ($this->_modules === null) {
            $modules = [];
            foreach (glob($this->alias->resolve('@app/*'), GLOB_ONLYDIR) as $dir) {
                if (is_file($dir . '/Module.php')) {
                    $modules[] = basename($dir);
                }
            }

            $this->_modules = $modules;
        }

        return $this->_modules;
    }
}