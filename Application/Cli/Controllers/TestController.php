<?php
namespace Application\Cli\Controllers;

class TestController extends \ManaPHP\Cli\Controller
{
    public function defaultCommand()
    {
        var_dump(get_included_files());
    }
}