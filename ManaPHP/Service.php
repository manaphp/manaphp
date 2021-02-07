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
            if (array_key_exists($option, $class_vars)) {
                $this->$option = $value;
            }
        }
    }

    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', static::class), 'Service');
    }
}