<?php
namespace ManaPHP\Configure;

use ManaPHP\Component;
use ManaPHP\Di;

/**
 * Class Configure
 *
 * @package ManaPHP
 */
class Configure extends Component implements ConfigureInterface
{
    public $debug = true;

    public function __debugInfo()
    {
        $data = [];

        foreach (get_object_vars($this) as $k => $v) {
            if (is_scalar($v) || is_array($v) || $v instanceof \stdClass) {
                $data[$k] = $v;
            }
        }
        return $data;
    }
}