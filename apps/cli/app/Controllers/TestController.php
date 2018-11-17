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
        dd(Country::query()->where(['country_id' => 87])->with('cities')->first());
    }
}