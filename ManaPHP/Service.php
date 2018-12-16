<?php
namespace ManaPHP;

use ManaPHP\Logger\LogCategorizable;

class Service extends Component implements LogCategorizable
{
    /**
     * Service constructor.
     *
     * @param array $options
     */
    public function __construct($options = null)
    {
        if (is_array($options)) {
            $vars = get_object_vars($this);
            foreach ($options as $name => $value) {
                $property = '_' . $name;

                if (isset($vars[$property]) || array_key_exists($property, $vars)) {
                    $this->$property = $value;
                }
            }
        }
    }

    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', get_called_class()), 'Service');
    }
}