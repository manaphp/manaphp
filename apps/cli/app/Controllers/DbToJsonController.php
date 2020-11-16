<?php

namespace App\Controllers;

use ManaPHP\Cli\Controller;

class DbToJsonController extends Controller
{
    public function defaultAction()
    {
        foreach ($this->db->getTables() as $table) {
            $f = fopen("d:/manaphp_unit_test/$table.json", 'wb+');
            $table = $this->db->getPrefix() . $table;
            foreach ($this->db->fetchAll("SELECT * FROM [$table]") as $row) {
                fwrite($f, json_encode($row) . PHP_EOL);
            }
            fclose($f);
        }
    }
}