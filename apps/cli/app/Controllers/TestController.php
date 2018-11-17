<?php
namespace App\Controllers;

use App\Models\Country;
use ManaPHP\Cli\Controller;

class TestController extends Controller
{
    /**
     * @CliCommand demo for cli write
     */
    public function defaultCommand()
    {
        var_dump(get_included_files());
    }
}