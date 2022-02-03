<?php
declare(strict_types=1);

namespace ManaPHP;

use ManaPHP\Logging\Logger\LogCategorizable;

class Service extends Component implements LogCategorizable
{
    public function __construct(array $options = [])
    {
        $class_vars = get_class_vars(static::class);

        foreach ($options as $option => $value) {
            if (array_key_exists($option, $class_vars)) {
                $this->$option = $value;
            }
        }
    }

    public function categorizeLog(): string
    {
        return basename(str_replace('\\', '.', static::class), 'Service');
    }
}