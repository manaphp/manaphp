<?php
namespace ManaPHP;

use ManaPHP\Logger\LogCategorizable;

class Controller extends Component implements LogCategorizable
{
    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', static::class), 'Controller');
    }

    /**
     * @return array
     */
    public function getAcl()
    {
        return [];
    }
}