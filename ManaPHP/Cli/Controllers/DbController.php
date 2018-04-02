<?php

namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Console;
use ManaPHP\Cli\Controller;
use ManaPHP\Db;
use ManaPHP\Utility\Text;

class DbController extends Controller
{
    /**
     * @return array
     */
    protected function _getDbServices()
    {
        $services = [];
        foreach ($this->configure->components as $service => $config) {
            $config = json_encode($config, JSON_UNESCAPED_SLASHES);
            if (preg_match('#(mysql|mssql|sqlite)://#', $config)) {
                $services[] = $service;
            }
        }
        return $services;
    }

    /**
     * @param string $service
     * @param string $pattern
     *
     * @return array
     */
    protected function _getTables($service, $pattern = null)
    {
        /**
         * @var \ManaPHP\DbInterface $db
         */
        $db = $this->_di->getShared($service);
        $tables = [];
        foreach ($db->getTables() as $table) {
            if ($pattern && !fnmatch($pattern, $table)) {
                continue;
            }
            $tables[] = $table;
        }

        sort($tables);

        return $tables;
    }

    /**
     * @param string $service
     * @param string $table
     * @param string $rootNamespace
     * @param bool   $optimized
     *
     * @return string
     */
    protected function _renderModel($service, $table, $rootNamespace = 'App\Models', $optimized = false)
    {
        $metadata = $this->_di->getShared($service)->getMetadata($table);

        $fields = (array)$metadata[Db::METADATA_ATTRIBUTES];

        $plainClass = Text::camelize($table);
        $modelName = $rootNamespace . '\\' . $plainClass;

        $str = '<?php' . PHP_EOL;
        $str .= 'namespace ' . substr($modelName, 0, strrpos($modelName, '\\')) . ';' . PHP_EOL;
        $str .= PHP_EOL;

        $str .= '/**' . PHP_EOL;
        $str .= ' * Class ' . $plainClass . PHP_EOL;
        $str .= ' */' . PHP_EOL;

        $str .= 'class ' . $plainClass . ' extends \ManaPHP\Db\Model' . PHP_EOL;
        $str .= '{';
        $str .= PHP_EOL;
        foreach ($fields as $field) {
            $str .= '    public $' . $field . ';' . PHP_EOL;
        }

        if ($service !== 'db') {
            $str .= PHP_EOL;
            $str .= '    /**' . PHP_EOL;
            $str .= '     * @param mixed $context' . PHP_EOL;
            $str .= '     *' . PHP_EOL;
            $str .= '     * @return string' . PHP_EOL;
            $str .= '     */' . PHP_EOL;
            $str .= '    public function getDb($context = null)' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= "        return '$service';" . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        if ($optimized) {
            $str .= PHP_EOL;

            $str .= '    /**' . PHP_EOL;
            $str .= '     * @param mixed $context' . PHP_EOL;
            $str .= '     *' . PHP_EOL;
            $str .= '     * @return string' . PHP_EOL;
            $str .= '     */' . PHP_EOL;
            $str .= '    public function getSource($context = null)' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= "        return '$table';" . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        if ($optimized) {
            $str .= PHP_EOL;
            $str .= '    /**' . PHP_EOL;
            $str .= '     * @return array' . PHP_EOL;
            $str .= '     */' . PHP_EOL;
            $str .= '    public function getFields()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= '        return [' . PHP_EOL;
            foreach ($fields as $field) {
                $str .= "            '$field'," . PHP_EOL;
            }
            $str .= '        ];' . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        $primaryKey = $metadata[Db::METADATA_PRIMARY_KEY];
        if ($optimized && $primaryKey) {
            $str .= PHP_EOL;
            $str .= '    /**' . PHP_EOL;
            $str .= '     * @return string' . PHP_EOL;
            $str .= '     */' . PHP_EOL;
            $str .= '    public function getPrimaryKey()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= "        return '$primaryKey[0]';" . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        $autoIncField = $metadata[Db::METADATA_AUTO_INCREMENT_KEY];
        if ($optimized) {
            $str .= PHP_EOL;
            $str .= '    /**' . PHP_EOL;
            $str .= '     * @return string' . PHP_EOL;
            $str .= '     */' . PHP_EOL;
            $str .= '    public function getAutoIncrementField()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            if ($autoIncField) {
                $str .= "        return '$autoIncField';" . PHP_EOL;
            } else {
                $str .= '        return null;' . PHP_EOL;
            }
            $str .= '    }' . PHP_EOL;
        }

        if ($optimized) {
            $intTypeFields = (array)$metadata[Db::METADATA_INT_TYPE_ATTRIBUTES];

            $str .= PHP_EOL;
            $str .= '    /**' . PHP_EOL;
            $str .= '     * @return array' . PHP_EOL;
            $str .= '     */' . PHP_EOL;
            $str .= '    public function getIntTypeFields()' . PHP_EOL;
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
            $str .= '    protected function _getCrudTimestampFields()' . PHP_EOL;
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
     * @CliCommand list databases and collections
     * @CliParam   --services:-s  explicit the mongodb services name
     * @CLiParam   --tables:-t table name
     */
    public function listCommand()
    {
        if ($this->arguments->hasOption('services:s')) {
            $services = explode(',', $this->arguments->getOption('services:s'));
        } else {
            $services = $this->_getDbServices();
        }
        $tables = $this->arguments->getOption('tables:t', '');

        foreach ($services as $service) {
            $this->console->writeLn(['service: `:service`', 'service' => $service], Console::FC_CYAN);

            foreach ($this->_getTables($service, $tables) as $row => $table) {
                /**
                 * @var \ManaPHP\DbInterface $db
                 */
                $db = $this->_di->getShared($service);

                $columns = (array)$db->getMetadata($table)[Db::METADATA_ATTRIBUTES];
                $primaryKey = $db->getMetadata($table)[Db::METADATA_PRIMARY_KEY];
                foreach ($columns as $i => $column) {
                    if (in_array($column, $primaryKey, true)) {
                        $columns[$i] = $this->console->colorize($column, Console::FC_RED);
                    }
                }

                $this->console->writeLn([' :row :table(:columns)',
                    'row' => sprintf('%2d ', $row + 1),
                    'table' => $this->console->colorize($table, Console::FC_GREEN),
                    'columns' => implode($columns, ', ')]);
            }
        }
    }

    /**
     * @CliCommand generate model file in online
     * @CliParam   --service:-s  explicit the mongodb service name
     * @CliParam   --table:-t table name
     * @CliParam   --optimized:-o output as more methods as possible (default: 0)
     * @throws \ManaPHP\Cli\Controllers\Exception
     */
    public function modelCommand()
    {
        /**
         * @var \ManaPHP\DbInterface $db
         */
        $table = $this->arguments->getOption('table:t');

        if ($this->arguments->hasOption('service:s')) {
            $service = $this->arguments->getOption('service:s');
            $db = $this->_di->getShared($service);
            $tables = $db->getTables();
            if (!in_array($table, $tables, true)) {
                throw new Exception(['`:table` is not exists', 'table' => $table]);
            }
        } else {
            $service = null;
            foreach ($this->_getDbServices() as $s) {
                $db = $this->_di->getShared($s);
                if (in_array($table, $db->getTables(), true)) {
                    $service = $s;
                    break;
                }
            }
            if (!$service) {
                throw new Exception(['`:table` is not found in services`', 'table' => $table]);
            }
        }

        $this->console->progress(['`:table` processing...', 'table' => $table], '');

        $plainClass = Text::camelize($table);
        $fileName = "@tmp/db_model/$service/$plainClass.php";
        $model_str = $this->_renderModel($service, $table);
        $this->filesystem->filePut($fileName, $model_str);

        $this->console->progress(['`:table` table saved to `:file`', 'table' => $table, 'file' => $fileName]);
    }

    /**
     * @CliCommand generate models file in online
     * @CliParam   --services:-s  explicit the mongodb services name
     * @CliParam   --tables:-t  tables with fnmatch method
     * @CliParam   --ns namespaces of models
     * @CliParam   --optimized:-o output as more methods as possible (default: 0)
     */
    public function modelsCommand()
    {
        if ($this->arguments->hasOption('services:s')) {
            $services = explode(',', $this->arguments->getOption('services:s'));
        } else {
            $services = $this->_getDbServices();
        }
        $tables = $this->arguments->getOption('tables:t', '');
        $optimized = $this->arguments->getOption('optimized:o', 0);
        $modelsNamespace = $this->arguments->getOption('ns', 'App\Models');
        foreach ($services as $service) {
            foreach ($this->_getTables($service, $tables) as $table) {
                $this->console->progress(['`:table` processing...', 'table' => $table], '');

                $plainClass = Text::camelize($table);
                $fileName = "@tmp/db_models/$service/$plainClass.php";
                $model_str = $this->_renderModel($service, $table, $modelsNamespace, $optimized);
                $this->filesystem->filePut($fileName, $model_str);

                $this->console->progress(['  `:table` table saved to `:file`', 'table' => $table, 'file' => $fileName]);
            }
        }
    }

    /**
     * @CliCommand export db data to csv files
     * @CliParam   --services:-s  explicit the db services name
     * @CliParam   --tables:-t export these tables only
     */
    public function jsonCommand()
    {
        if ($this->arguments->hasOption('services:s')) {
            $services = explode(',', $this->arguments->getOption('services:s'));
        } else {
            $services = $this->_getDbServices();
        }
        $tables = $this->arguments->getOption('tables:t', '');

        foreach ($services as $service) {
            /**
             * @var \ManaPHP\DbInterface $db
             */
            $db = $this->_di->getShared($service);
            foreach ($this->_getTables($service, $tables) as $table) {
                $fileName = "@tmp/db_json/$service/$table.json";

                $this->console->progress(['`:table` processing...', 'table' => $table], '');

                $this->filesystem->dirCreate(dirname($fileName));
                $rows = $db->fetchAll("SELECT * FROM [$table]");
                $file = fopen($this->alias->resolve($fileName), 'wb');

                $startTime = microtime(true);
                foreach ($rows as $row) {
                    fwrite($file, json_encode($row) . PHP_EOL);
                }
                fclose($file);

                $this->console->progress([
                    'write to `:file` success: :count [:time]',
                    'file' => $fileName,
                    'count' => count($rows),
                    'time' => round(microtime(true) - $startTime, 4)
                ]);
            }
        }
    }

    /**
     * @CliCommand export db data to csv files
     * @CliParam   --service:-s  explicit the db service name
     * @CliParam   --tables:-t export these tables only
     * @CliParam   --bom  contains BOM or not (default: 0)
     * @throws \ManaPHP\Db\Exception
     */
    public function csvCommand()
    {
        if ($this->arguments->hasOption('services:s')) {
            $services = explode(',', $this->arguments->getOption('services:s'));
        } else {
            $services = $this->_getDbServices();
        }

        $tables = $this->arguments->getOption('tables:t', '');
        $bom = $this->arguments->getOption('bom', 0);

        foreach ($services as $service) {
            /**
             * @var \ManaPHP\Db $db
             */
            $db = $this->_di->getShared($service);
            foreach ($this->_getTables($service, $tables) as $table) {
                $this->console->progress(['`:table` processing...', 'table' => $table], '');

                $fileName = "@tmp/db_csv/$service/$table.csv";
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
                    ' `:table` imported to `:file`: :count [:time]',
                    'table' => $table,
                    'file' => $fileName,
                    'count' => count($rows),
                    'time' => round(microtime(true) - $startTime, 4)
                ]);
            }
        }
    }
}