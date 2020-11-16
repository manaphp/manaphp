<?php

namespace App\Controllers;

use ManaPHP\Cli\Controller;

/**
 * Class TimeController
 *
 * @package App\Controllers
 *
 * @property-read \App\Services\TimeService $timeService
 */
class TimeController extends Controller
{
    public function defaultAction()
    {
        $current = date('Y-m-d H:i:s', $this->timeService->current());
        var_dump($current);
    }
}