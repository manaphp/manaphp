<?php
namespace Application\Home\Widgets {

    use ManaPHP\Mvc\Widget;

    class CopyrightWidget extends Widget
    {

        public function run($options)
        {
            $vars = [];

            $vars['year'] = date('Y');

            return $vars;
        }
    }
}