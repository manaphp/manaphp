<?php
namespace App\Home\Widgets;

use ManaPHP\View\Widget;

class CopyrightWidget extends Widget
{
    public function run($options = [])
    {
        $vars = [];

        $vars['year'] = date('Y');

        return $vars;
    }
}