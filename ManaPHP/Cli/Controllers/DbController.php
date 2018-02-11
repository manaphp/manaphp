<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Console;
use ManaPHP\Cli\Controller;
use ManaPHP\Db;
use ManaPHP\Utility\Text;

class DbController extends Controller
{
    /**
     * @CliCommand list databases and collections
     * @CliParam   --service:-s  explicit the mongodb service name
     * @CliParam   --filter:-f filter the tables with fnmath method
     */
    public function listCommand()
    {
        /**
         * @var \ManaPHP\Db $db
         */
        $db = $this->_dependencyInjector->getShared($this->arguments->getOption('service:s', 'db'));

        $tables = $db->getTables();
        sort($tables);

        $filter = $this->arguments->getOption('filter:f', '');
        foreach ($tables as $table) {
            if ($filter && !fnmatch($filter, $table)) {
                continue;
            }

            $columns = $db->getMetadata($table)[Db::METADATA_ATTRIBUTES];
            $primaryKey = $db->getMetadata($table)[Db::METADATA_PRIMARY_KEY];
            foreach ($columns as $i => $column) {
                if ($primaryKey && $column === $primaryKey[0]) {
                    $columns[$i] = $this->console->colorize($column, Console::FC_RED);
                }
            }
            $this->console->writeLn($this->console->colorize($table, Console::FC_GREEN) . ('(' . implode($columns, ', ') . ')'));
        }

        $this->console->writeLn();
        $this->console->writeLn(['total `:count` tables', 'count' => count($tables)]);
    }

    /**
     * @CliCommand generate model file in online
     * @CliParam   --service:-s  explicit the mongodb service name
     * @CliParam   --optimized:-o output as more methods as possible (default: 0)
     * @throws \ManaPHP\Cli\Controllers\Exception
     */
    public function modelCommand()
    {
        /**
         * @var \ManaPHP\Db $db
         */
        $db = $this->_dependencyInjector->getShared($this->arguments->getOption('service:s', 'db'));
        $table = $this->arguments->getOption('table:t');

        $tables = $db->getTables();
        if (!in_array($table, $tables, true)) {
            throw new Exception('`:table` is not exists: :tables`', ['table' => $table, 'tables' => implode($tables, ', ')]);
        }

        $plainClass = Text::camelize($table);
        $model = $this->_renderModel($db, $table);
        $this->filesystem->filePut("@data/tmp/db/model/$plainClass.php", $model);
    }

    /**
     * @CliCommand generate models file in online
     * @CliParam   --service:-s  explicit the mongodb service name
     * @CliParam   --filter:-f filter the tables with fnmath method
     * @CliParam   --ns namespaces of models
     * @CliParam   --optimized:-o output as more methods as possible (default: 0)
     */
    public function modelsCommand()
    {
        /**
         * @var \ManaPHP\Db $db
         */
        $db = $this->_dependencyInjector->getShared($this->arguments->getOption('service:s', 'db'));
        $tables = $db->getTables();
        sort($tables);

        $filter = $this->arguments->getOption('filter:f', '');
        foreach ($tables as $table) {
            if ($filter && !fnmatch($filter, $table)) {
                continue;
            }

            $plainClass = Text::camelize($table);
            $model = $this->_renderModel($db, $table);
            $this->filesystem->filePut("@data/tmp/db/models/$plainClass.php", $model);
        }
    }

    /**
     * @param \ManaPHP\Db $db
     * @param string      $table
     *
     * @return string
     */
    protected function _renderModel($db, $table)
    {
        $optimized = $this->arguments->getOption('optimized:o', 0);

        $fields = (array)$db->getMetadata($table)[Db::METADATA_ATTRIBUTES];

        $plainClass = Text::camelize($table);
        $modelName = $this->arguments->getOption('ns', 'App\Models') . '\\' . $plainClass;

        $str = '<?php' . PHP_EOL;
        $str .= 'namespace ' . substr($modelName, 0, strrpos($modelName, '\\')) . ';' . PHP_EOL;
        $str .= PHP_EOL;

        $str .= 'class ' . substr($modelName, strrpos($modelName, '\\') + 1) . ' extends \ManaPHP\Db\Model' . PHP_EOL;
        $str .= '{';
        $str .= PHP_EOL;
        foreach ($fields as $field) {
            $str .= '    public $' . $field . ';' . PHP_EOL;
        }

        if ($optimized) {
            $str .= PHP_EOL;
            $str .= '    /** Returns table name mapped in the model' . PHP_EOL;
            $str .= '     *' . PHP_EOL;
            $str .= '     * @param mixed $context' . PHP_EOL;
            $str .= '     *' . PHP_EOL;
            $str .= '     * @return string|false' . PHP_EOL;
            $str .= '     */' . PHP_EOL;
            $str .= '    public static function getSource($context = null)' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= "        return '$table';" . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        if ($optimized) {
            $str .= PHP_EOL;
            $str .= '    /**' . PHP_EOL;
            $str .= '     * @return array' . PHP_EOL;
            $str .= '     */' . PHP_EOL;
            $str .= '    public static function getFields()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= '        return [' . PHP_EOL;
            foreach ($fields as $field) {
                $str .= "            '$field'," . PHP_EOL;
            }
            $str .= '        ];' . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        $primaryKey = $db->getMetadata($table)[Db::METADATA_PRIMARY_KEY];
        if ($optimized && $primaryKey) {
            $str .= PHP_EOL;
            $str .= '    /**' . PHP_EOL;
            $str .= '     * @return string' . PHP_EOL;
            $str .= '     */' . PHP_EOL;
            $str .= '    public static function getPrimaryKey()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= "        return '$primaryKey[0]';" . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        $autoIncField = $db->getMetadata($table)[Db::METADATA_AUTO_INCREMENT_KEY];
        if ($optimized) {
            $str .= PHP_EOL;
            $str .= '    /**' . PHP_EOL;
            $str .= '     * @return string' . PHP_EOL;
            $str .= '     */' . PHP_EOL;
            $str .= '    public static function getAutoIncrementField()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            if ($autoIncField) {
                $str .= "        return '$autoIncField';" . PHP_EOL;
            } else {
                $str .= '        return null;' . PHP_EOL;
            }
            $str .= '    }' . PHP_EOL;
        }

        if ($optimized) {
            $intTypeFields = (array)$db->getMetadata($table)[Db::METADATA_INT_TYPE_ATTRIBUTES];

            $str .= PHP_EOL;
            $str .= '    /**' . PHP_EOL;
            $str .= '     * @return array' . PHP_EOL;
            $str .= '     */' . PHP_EOL;
            $str .= '    public static function getIntTypeFields()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= '        return [' . PHP_EOL;
            foreach ($intTypeFields as $field) {
                $str .= "            '$field'," . PHP_EOL;
            }
            $str .= '        ];' . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        $crudTimestampFields = [];
        $intersect = array_intersect(['created_time', 'created_at'], $fields);
        if ($intersect) {
            $crudTimestampFields['create'] = $intersect[0];
        }

        $intersect = array_intersect(['updated_time', 'updated_at'], $fields);
        if ($intersect) {
            $crudTimestampFields['update'] = $intersect[0];
        }

        if ($optimized && $crudTimestampFields) {
            $str .= PHP_EOL;
            $str .= '    /**' . PHP_EOL;
            $str .= '     * @return array' . PHP_EOL;
            $str .= '     */' . PHP_EOL;
            $str .= '    protected static function _getCrudTimestampFields()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= '        return [' . PHP_EOL;
            foreach ($crudTimestampFields as $name => $field) {
                $str .= "            '$name' => '$field'," . PHP_EOL;
            }
            $str .= '        ];' . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        $str .= '}';

        return $str;
    }

    /**
     * @CliCommand export db data to csv files
     * @CliParam   --service:-s  explicit the db service name
     * @CliParam   --table:-t export these tables only
     */
    public function jsonCommand()
    {
        /**
         * @var \ManaPHP\Db $db
         */
        $db = $this->_dependencyInjector->getShared($this->arguments->getOption('service:s', 'db'));
        $tables = $db->getTables();
        sort($tables);

        $filterTables = $this->arguments->getOption('table:t', '');

        foreach ($tables as $table) {
            if ($filterTables && strpos($filterTables, $table) === false) {
                continue;
            }

            $fileName = "@data/tmp/db/json/$table.json";

            $this->console->writeLn(['`:table` processing...', 'table' => $table]);

            $this->filesystem->dirCreate(dirname($fileName));
            $rows = $db->fetchAll("SELECT * FROM [$table]");
            $file = fopen($this->alias->resolve($fileName), 'wb');

            $startTime = microtime(true);
            foreach ($rows as $row) {
                fwrite($file, json_encode($row) . PHP_EOL);
            }
            fclose($file);

            $this->console->writeLn(['write to `:file` success: :count [:time]', 'file' => $fileName, 'count' => count($rows), 'time' => round(microtime(true) - $startTime, 4)]);
            /** @noinspection DisconnectedForeachInstructionInspection */
            $this->console->writeLn();
        }
    }

    /**
     * @CliCommand export db data to csv files
     * @CliParam   --service:-s  explicit the db service name
     * @CliParam   --table:-t export these tables only
     * @CliParam   --bom  contains BOM or not (default: 0)
     * @throws \ManaPHP\Db\Exception
     */
    public function csvCommand()
    {
        /**
         * @var \ManaPHP\Db $db
         */
        $db = $this->_dependencyInjector->getShared($this->arguments->getOption('service:s', 'db'));
        $tables = $db->getTables();
        sort($tables);

        $bom = $this->arguments->getOption('bom', 0);
        $filterTables = $this->arguments->getOption('table:t', '');
        foreach ($tables as $table) {
            if ($filterTables && strpos($filterTables, $table) === false) {
                continue;
            }

            $fileName = "@data/tmp/db/csv/$table.csv";

            $this->console->progress(['`:table` processing...', 'table' => $table], '');

            $this->filesystem->dirCreate(dirname($fileName));
            $rows = $db->fetchAll("SELECT * FROM [$table]");

            $file = fopen($this->alias->resolve($fileName), 'wb');

            if ($bom) {
                fprintf($file, "\xEF\xBB\xBF");
            }

            if ($rows) {
                fputcsv($file, array_keys($rows[0]));
            }

            $startTime = microtime(true);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);

            $this->console->progress([
                ' `:table` table imported to `:file`: :count [:time]',
                'table' => $table,
                'file' => $fileName,
                'count' => count($rows),
                'time' => round(microtime(true) - $startTime, 4)
            ]);
        }
    }
}