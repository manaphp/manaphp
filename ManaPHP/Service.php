<?php

namespace ManaPHP;

use ManaPHP\Logging\Logger\LogCategorizable;

class Service extends Component implements LogCategorizable
{
    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        $class_vars = get_class_vars(static::class);

        foreach ($options as $option => $value) {
            $property = "_$option";
            if (array_key_exists($property, $class_vars)) {
                $this->$property = $value;
            }
        }
    }

    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', static::class), 'Service');
    }
}