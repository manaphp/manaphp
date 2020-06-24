<?php

namespace App\Controllers;

use ManaPHP\Cli\Controller;
use Swoole\Coroutine;

class TestController extends Controller
{
    /**
     * @CliCommand demo for cli write
     */
    public function defaultCommand()
    {
        $returns = $this->coroutineManager->createScheduler()
            ->add(function () {
                rest_get()
                Coroutine::sleep(mt_rand(1, 1000) / 1000);
                var_dump('a');
                return 'a';
            })->add(function () {
                Coroutine::sleep(mt_rand(1, 1000) / 1000);
                var_dump('b');
                return 'b';
            })->start();

        var_dump($returns);
    }
}
