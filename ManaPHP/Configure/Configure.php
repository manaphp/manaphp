<?php
namespace ManaPHP\Configure;

use ManaPHP\Component;

/**
 * Class Configure
 *
 * @package ManaPHP
 */
class Configure extends Component implements ConfigureInterface
{
    /**
     * @var bool
     */
    public $debug = true;

    /**
     * @return array
     */
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