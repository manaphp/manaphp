<?php
namespace Application\Admin\Tasks;

use ManaPHP\Utility\File;

class TestTask extends TaskBase
{

    public function run()
    {
        $file = $this->alias->resolve('@data/Tasks/test.log');
        File::appendContent($file, date('Y-m-d H:i:s') . PHP_EOL);
        sleep(1);
    }
}