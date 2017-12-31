<?php
namespace App\Admin\Tasks;

class TestTask extends TaskBase
{
    public function run()
    {
        $this->filesystem->fileAppend('@data/Tasks/test.log', date('Y-m-d H:i:s') . PHP_EOL);
        sleep(1);
    }
}