<?php
namespace ManaPHP\Configure;

use ManaPHP\Component;

/**
 * Class ManaPHP\Configure\Configure
 *
 * @package configure
 */
class Configure extends Component implements ConfigureInterface
{
    /**
     * @var bool
     */
    public $debug = true;

    /**
     * @var string
     */
    protected $_masterKey = 'key';

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

    /**
     * @param string $type
     *
     * @return string
     */
    public function getSecretKey($type)
    {
        return md5($this->_masterKey . ':' . $type);
    }
}