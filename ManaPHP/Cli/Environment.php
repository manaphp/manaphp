<?php
namespace ManaPHP\Cli;

use ManaPHP\Component;

class Environment extends Component implements EnvironmentInterface
{
    /**
     * @param string $name
     * @param string $defaultValue
     *
     * @return string
     */
    public function get($name, $defaultValue = null)
    {
        $r = getenv($name);
        if ($r === false) {
            return $defaultValue;
        } else {
            return $r;
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return getenv($name) !== false;
    }
}