<?php
namespace Application\Cli\Controllers;

use ManaPHP\Cli\Controller;
use ManaPHP\Db;

class DbToJsonController extends Controller
{
    public function defaultCommand()
    {
        foreach ($this->db->getTables() as $table) {
            $f = fopen("d:/manaphp_unit_test/$table.json", 'wb+');
            $pk = $this->db->getMetadata($table)[Db::METADATA_PRIMARY_KEY];
            foreach ($this->db->fetchAll("SELECT * FROM [$table]") as $row) {
                if (count($pk) === 1) {
                    $row = ['_id' => $row[$pk[0]]] + $row;
                }

                fwrite($f, json_encode($row) . PHP_EOL);
            }
            fclose($f);
        }
    }
}