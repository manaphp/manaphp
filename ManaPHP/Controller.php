<?php
namespace ManaPHP;

use ManaPHP\Logger\LogCategorizable;

abstract class Controller extends Component implements LogCategorizable
{
    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', get_called_class()), 'Controller');
    }
}