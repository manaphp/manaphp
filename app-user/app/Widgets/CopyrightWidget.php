<?php

namespace App\Widgets;

class CopyrightWidget extends Widget
{
    public function run($options = [])
    {
        $vars = [];

        $vars['year'] = date('Y');

        return $vars;
    }
}