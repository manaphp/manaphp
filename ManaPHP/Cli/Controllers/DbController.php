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
     * list databases and tables
     *
     * @param array  $services services name list
     * @param string $table_pattern match table against a pattern
     */
    public function listCommand($services = [], $table_pattern = '')
    {
        foreach ($services ?: $this->_getDbServices() as $service) {
            /**
             * @var \ManaPHP\DbInterface $db
             */
            $db = $this->_di->getShared($service);

            $this->console->writeLn(['service: `:service`', 'service' => $service], Console::FC_CYAN);
            foreach ($this->_getTables($service, $table_pattern) as $row => $table) {
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
     * generate model file in online
     *
     * @param string $service service name
     * @param string $table table name
     * @param string $namespace
     * @param bool   $optimized output as more methods as possible
     *
     * @throws \ManaPHP\Cli\Controllers\Exception
     */
    public function modelCommand($service = '', $table, $namespace = 'App\Models', $optimized = false)
    {
        /**
         * @var \ManaPHP\DbInterface $db
         */
        if ($service) {
            $db = $this->_di->getShared($service);
            if (!in_array($table, $db->getTables(), true)) {
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
        $model_str = $this->_renderModel($service, $table, $namespace, $optimized);
        $this->filesystem->filePut($fileName, $model_str);

        $this->console->progress(['`:table` table saved to `:file`', 'table' => $table, 'file' => $fileName]);
    }

    /**
     * generate models file in online
     *
     * @param array  $services services name list
     * @param string $table_pattern match table against a pattern
     * @param string $namespace namespace of models
     * @param bool   $optimized output as more methods as possible
     */
    public function modelsCommand($services = [], $table_pattern = '', $namespace = 'App\Models', $optimized = false)
    {
        foreach ($services ?: $this->_getDbServices() as $service) {
            foreach ($this->_getTables($service, $table_pattern) as $table) {
                $this->console->progress(['`:table` processing...', 'table' => $table], '');

                $plainClass = Text::camelize($table);
                $fileName = "@tmp/db_models/$service/$plainClass.php";
                $model_str = $this->_renderModel($service, $table, $namespace, $optimized);
                $this->filesystem->filePut($fileName, $model_str);

                $this->console->progress(['  `:table` table saved to `:file`', 'table' => $table, 'file' => $fileName]);
            }
        }
    }

    /**
     * export db data to csv files
     *
     * @param array  $services services name list
     * @param string $table_pattern match table against a pattern
     */
    public function jsonCommand($services = [], $table_pattern = '')
    {
        foreach ($services ?: $this->_getDbServices() as $service) {
            /**
             * @var \ManaPHP\DbInterface $db
             */
            $db = $this->_di->getShared($service);
            foreach ($this->_getTables($service, $table_pattern) as $table) {
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
     * export db data to csv files
     *
     * @param array  $services services name list
     * @param string $table_pattern match table against a pattern
     * @param bool   $bom contains BOM or not
     */
    public function csvCommand($services = [], $table_pattern = '', $bom = false)
    {
        foreach ($services ?: $this->_getDbServices() as $service) {
            /**
             * @var \ManaPHP\Db $db
             */
            $db = $this->_di->getShared($service);
            foreach ($this->_getTables($service, $table_pattern) as $table) {
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