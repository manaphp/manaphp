<?php
namespace Application\Cli\Controllers;

class TestController extends \ManaPHP\Cli\Controller
{
    public function defaultAction()
    {
        var_dump(get_included_files());
    }
}