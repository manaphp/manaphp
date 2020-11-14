<?php

namespace App\Widgets;

use ManaPHP\Mvc\View\Widget;

class CopyrightWidget extends Widget
{
    public function run($options = [])
    {
        $vars = [];

        $vars['year'] = date('Y');

        return $vars;
    }
}