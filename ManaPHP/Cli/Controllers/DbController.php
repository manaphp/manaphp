<?php

namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Console;
use ManaPHP\Cli\Controller;
use ManaPHP\Db;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;

class DbController extends Controller
{
    /**
     * @return array
     */
    protected function _getDbServices()
    {
        $services = [];
        foreach ($this->configure->components as $service => $config) {
            $config = json_stringify($config);
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
        /** @var \ManaPHP\DbInterface $db */
        $db = $this->getShared($service);
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
     * @param string $modelName
     *
     * @return string
     */
    protected function _getConstantsByFile($modelName)
    {
        $file = "@app/Models/$modelName.php";
        if (!LocalFS::fileExists($file)) {
            return '';
        }

        $constants = '';
        foreach (file($this->alias->resolve($file)) as $line) {
            if (preg_match('#^\s+const\s+[A-Z0-9_]+\s*=#', $line) === 1) {
                $constants .= $line;
            } elseif (trim($line) === '') {
                $constants .= PHP_EOL;
            }
        }

        return trim($constants);
    }

    /**
     * @param string $service
     * @param string $table
     *
     * @return string
     */
    protected function _getConstantsByDb($service, $table)
    {
        static $cached;

        if (!isset($cached[$service])) {
            /** @var \ManaPHP\DbInterface $db */
            $db = $this->getShared($service);
            $metadata_table = 'metadata_constant';
            if (!in_array($metadata_table, $db->getTables(), true)) {
                $cached[$service] = [];
            } else {
                $metadata_table = $db->getPrefix() . $metadata_table;
                $rows = $db->fetchAll(/**@lang text */ "SELECT `id`, `constants` FROM $metadata_table");
                foreach ($rows as $row) {
                    $cached[$service][$row['id']] = $row['constants'];
                }
            }
        }

        if (!isset($cached[$service][$table])) {
            return '';
        }

        $lines = [];
        $constants = preg_split('#[\r\n]{1,2}#m', trim($cached[$service][$table]));
        foreach ($constants as $constant) {
            $constant = trim($constant);
            if ($constant === '') {
                $lines[] = '';
                continue;
            }

            if (!str_contains($constant, ';')) {
                $constant .= ';';
            }
            $lines[] = '    const ' . $constant;
        }

        return implode(PHP_EOL, $lines);
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
        /** @var Db $db */
        $db = $this->getShared($service);
        $metadata = $db->getMetadata($table);

        $fields = (array)$metadata[Db::METADATA_ATTRIBUTES];

        $plainClass = Str::camelize($table);
        $modelName = $rootNamespace . '\\' . $plainClass;

        if ($constants = $this->_getConstantsByDb($service, $table)) {
            null;
        } elseif ($constants = $this->_getConstantsByFile($plainClass)) {
            $constants = '    ' . $constants;
        }

        $str = '<?php' . PHP_EOL;
        $str .= 'namespace ' . substr($modelName, 0, strrpos($modelName, '\\')) . ';' . PHP_EOL;
        $str .= PHP_EOL;
        $str .= 'use ManaPHP\Db\Model;' . PHP_EOL;
        $str .= PHP_EOL;

        $str .= '/**' . PHP_EOL;
        $str .= ' * Class ' . $modelName . PHP_EOL;
        $str .= ' */' . PHP_EOL;

        $str .= 'class ' . $plainClass . ' extends Model' . PHP_EOL;
        $str .= '{';
        if ($constants) {
            $str .= PHP_EOL . $constants . PHP_EOL;
        }

        $str .= PHP_EOL;
        foreach ($fields as $field) {
            $str .= '    public $' . $field . ';' . PHP_EOL;
        }

        if ($service !== 'db') {
            $str .= PHP_EOL;
            $str .= '    /**' . PHP_EOL;
            $str .= '     * @return string' . PHP_EOL;
            $str .= '     */' . PHP_EOL;
            $str .= '    public function getDb()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= "        return '$service';" . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        if (true) {
            $str .= PHP_EOL;

            $str .= '    /**' . PHP_EOL;
            $str .= '     * @return string' . PHP_EOL;
            $str .= '     */' . PHP_EOL;
            $str .= '    public function getTable()' . PHP_EOL;
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
        if ($primaryKey) {
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
            $intFields = (array)$metadata[Db::METADATA_INT_TYPE_ATTRIBUTES];

            $str .= PHP_EOL;
            $str .= '    /**' . PHP_EOL;
            $str .= '     * @return array' . PHP_EOL;
            $str .= '     */' . PHP_EOL;
            $str .= '    public function getIntFields()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= '        return [' . PHP_EOL;
            foreach ($intFields as $field) {
                $str .= "            '$field'," . PHP_EOL;
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
     * @param array  $services      services name list
     * @param string $table_pattern match table against a pattern
     */
    public function listCommand($services = [], $table_pattern = '')
    {
        foreach ($services ?: $this->_getDbServices() as $service) {
            /** @var \ManaPHP\DbInterface $db */
            $db = $this->getShared($service);

            $this->console->writeLn(['service: `:service`', 'service' => $service], Console::FC_CYAN);
            foreach ($this->_getTables($service, $table_pattern) as $row => $table) {
                $columns = (array)$db->getMetadata($table)[Db::METADATA_ATTRIBUTES];
                $primaryKey = $db->getMetadata($table)[Db::METADATA_PRIMARY_KEY];
                foreach ($columns as $i => $column) {
                    if (in_array($column, $primaryKey, true)) {
                        $columns[$i] = $this->console->colorize($column, Console::FC_RED);
                    }
                }

                $colored_table = $this->console->colorize($table, Console::FC_GREEN);
                $this->console->writeLn(['%2d %s(%s)', $row + 1, $colored_table, implode(', ', $columns)]);
            }
        }
    }

    /**
     * generate model file in online
     *
     * @param string $table     table name
     * @param string $service   service name
     * @param string $namespace
     * @param bool   $optimized output as more methods as possible
     *
     * @throws \ManaPHP\Cli\Controllers\Exception
     */
    public function modelCommand($table, $service = '', $namespace = 'App\Models', $optimized = false)
    {
        if (!str_contains($namespace, '\\')) {
            $namespace = 'App\\' . ucfirst($namespace) . '\\Models';
        }

        /** @var \ManaPHP\DbInterface $db */
        if ($service) {
            $db = $this->getShared($service);
            if (!in_array($table, $db->getTables(), true)) {
                throw new Exception(['`:table` is not exists', 'table' => $table]);
            }
        } else {
            foreach ($this->_getDbServices() as $s) {
                $db = $this->getShared($s);
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

        $plainClass = Str::camelize($table);
        $fileName = "@tmp/db_model/$plainClass.php";
        $model_str = $this->_renderModel($service, $table, $namespace, $optimized);
        LocalFS::filePut($fileName, $model_str);

        $this->console->progress(['`:table` table saved to `:file`', 'table' => $table, 'file' => $fileName]);
    }

    /**
     * generate models file in online
     *
     * @param array  $services      services name list
     * @param string $table_pattern match table against a pattern
     * @param string $namespace     namespace of models
     * @param bool   $optimized     output as more methods as possible
     */
    public function modelsCommand($services = [], $table_pattern = '', $namespace = 'App\Models', $optimized = false)
    {
        if (!str_contains($namespace, '\\')) {
            $namespace = 'App\\' . ucfirst($namespace) . '\\Models';
        }

        foreach ($services ?: $this->_getDbServices() as $service) {
            foreach ($this->_getTables($service, $table_pattern) as $table) {
                $this->console->progress(['`:table` processing...', 'table' => $table], '');

                $plainClass = Str::camelize($table);
                $fileName = "@tmp/db_models/$plainClass.php";
                $model_str = $this->_renderModel($service, $table, $namespace, $optimized);
                LocalFS::filePut($fileName, $model_str);

                $this->console->progress(['  `:table` table saved to `:file`', 'table' => $table, 'file' => $fileName]);
            }
        }
    }

    /**
     * export db data to csv files
     *
     * @param array  $services      services name list
     * @param string $table_pattern match table against a pattern
     */
    public function jsonCommand($services = [], $table_pattern = '')
    {
        foreach ($services ?: $this->_getDbServices() as $service) {
            /** @var \ManaPHP\DbInterface $db */
            $db = $this->getShared($service);
            foreach ($this->_getTables($service, $table_pattern) as $table) {
                $fileName = "@tmp/db_json/$service/$table.json";

                $this->console->progress(['`:table` processing...', 'table' => $table], '');

                LocalFS::dirCreate(dirname($fileName));
                $table = $db->getPrefix() . $table;
                $rows = $db->fetchAll("SELECT * FROM [$table]");
                $file = fopen($this->alias->resolve($fileName), 'wb');

                $startTime = microtime(true);
                foreach ($rows as $row) {
                    fwrite($file, json_stringify($row) . PHP_EOL);
                }
                fclose($file);

                $elapsed = microtime(true) - $startTime;
                $this->console->progress(['write to `%s` success: %d [%.3f]', $fileName, count($rows), $elapsed]);
            }
        }
    }

    /**
     * export db data to csv files
     *
     * @param array  $services      services name list
     * @param string $table_pattern match table against a pattern
     * @param bool   $bom           contains BOM or not
     */
    public function csvCommand($services = [], $table_pattern = '', $bom = false)
    {
        foreach ($services ?: $this->_getDbServices() as $service) {
            /** @var \ManaPHP\Db $db */
            $db = $this->getShared($service);
            foreach ($this->_getTables($service, $table_pattern) as $table) {
                $this->console->progress(['`:table` processing...', 'table' => $table], '');

                $fileName = "@tmp/db_csv/$service/$table.csv";
                LocalFS::dirCreate(dirname($fileName));
                $table = $db->getPrefix() . $table;
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

                $count = count($rows);
                $elapsed = microtime(true) - $startTime;
                $this->console->progress([' `%s` imported to `%s`:%d [%.3f]', $table, $fileName, $count, $elapsed]);
            }
        }
    }
}