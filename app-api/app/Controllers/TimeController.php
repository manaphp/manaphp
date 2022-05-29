<?php
declare(strict_types=1);

namespace App\Controllers;

class TimeController extends Controller
{
    public function currentAction()
    {
        $data = [];
        $data['current_time'] = date('Y-m-d H:i:s');
        $data['memory_usage'] = round(memory_get_usage(false) / 1024) . 'KB';

        return $data;
    }
}
