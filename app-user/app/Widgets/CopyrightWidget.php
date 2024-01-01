<?php
declare(strict_types=1);

namespace App\Widgets;

class CopyrightWidget extends Widget
{
    public function run(array $vars = [])
    {
        $vars['year'] = date('Y');

        return $vars;
    }
}