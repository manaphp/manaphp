<?php
namespace App\Cli\Controllers;

use ManaPHP\Cli\Controller;
use ManaPHP\Db;

class DbToJsonController extends Controller
{
    public function defaultCommand()
    {
        foreach ($this->db->getTables() as $table) {
            $f = fopen("d:/manaphp_unit_test/$table.json", 'wb+');
            foreach ($this->db->fetchAll("SELECT * FROM [$table]") as $row) {
                fwrite($f, json_encode($row) . PHP_EOL);
            }
            fclose($f);
        }
    }
}