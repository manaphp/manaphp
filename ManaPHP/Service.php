<?php
namespace ManaPHP;

use ManaPHP\Logger\LogCategorizable;

class Service extends Component implements LogCategorizable
{
    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', get_called_class()), 'Service');
    }
}