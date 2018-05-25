<?php

namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Console;
use ManaPHP\Cli\Controller;
use ManaPHP\Utility\Text;

class MongodbController extends Controller
{
    /**
     * @return array
     */
    protected function _getDbServices()
    {
        $services = [];
        foreach ($this->configure->components as $service => $config) {
            $config = json_encode($config, JSON_UNESCAPED_SLASHES);
            if (preg_match('#mongodb://#', $config)) {
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
         * @var \ManaPHP\MongodbInterface $mongodb
         */
        $mongodb = $this->_di->getShared($service);
        $tables = [];
        foreach ($mongodb->listCollections() as $table) {
            if ($pattern && !fnmatch($pattern, $table)) {
                continue;
            }
            $tables[] = $table;
        }

        sort($tables);

        return $tables;
    }

    /**
     * generate model file from base64 encoded string
     *
     * @param string $input     the base64 encoded json string
     * @param string $modelName
     * @param bool   $optimized output as more methods as possible
     */
    public function modelCommand($input, $modelName, $optimized = false)
    {
        if (strpos($modelName, '\\') === false) {
            $modelName = 'App\\Models\\' . ucfirst($modelName);
        }

        $fieldTypes = $this->_inferFieldTypes([$input]);
        $model = $this->_renderModel($fieldTypes, $modelName, 'mongodb', $optimized);
        $file = '@tmp/mongodb_model/' . substr($modelName, strrpos($modelName, '\\') + 1) . '.php';
        $this->filesystem->filePut($file, $model);

        $this->console->writeLn(['write model to :file', 'file' => $file]);
    }

    /**
     * generate models file from data files or online data
     *
     * @param array  $services  explicit the mongodb service name
     * @param string $dir       the data file directory name
     * @param string $namespace namespaces of models
     * @param bool   $optimized output as more methods as possible
     * @param int    $sample    sample size
     */
    public function modelsCommand($services = [], $dir = '', $namespace = 'App\Models', $optimized = false, $sample = 1000)
    {
        if (strpos($namespace, '\\') === false) {
            $namespace = 'App\\' . ucfirst($namespace) . '\\Models';
        }

        if ($dir) {
            if (!$this->filesystem->dirExists($dir)) {
                throw new Exception(['`:dir` dir is not exists', 'dir' => $dir]);
            }

            foreach ($this->filesystem->glob($dir . '/*.json') as $file) {
                $lines = file($file);
                if (!isset($lines[0])) {
                    continue;
                }
                $fieldTypes = $this->_inferFieldTypes($lines[0]);
                $fileName = basename($file, '.json');
                $plainClass = Text::camelize($fileName);
                $modelClass = $namespace . '\\' . $plainClass;

                $model = $this->_renderModel($fieldTypes, $modelClass, 'mongodb', $optimized);

                $this->filesystem->filePut("@tmp/mongodb/models/$plainClass.php", $model);
            }
        } else {
            foreach ($services ?: $this->_getDbServices() as $service) {
                /**
                 * @var \ManaPHP\Mongodb $mongodb
                 */
                $mongodb = $this->_di->getShared($service);
                foreach ($mongodb->listCollections() as $collection) {
                    if (!$docs = $mongodb->aggregate($collection, [['$sample' => ['size' => $sample]]])) {
                        continue;
                    }

                    $plainClass = Text::camelize($collection);
                    $fileName = "@tmp/mongodb_models/$plainClass.php";

                    $this->console->progress(['`:collection` processing...', 'collection' => $collection], '');

                    $fieldTypes = $this->_inferFieldTypes($docs);
                    $modelClass = $namespace . '\\' . $plainClass;
                    $model = $this->_renderModel($fieldTypes, $modelClass, $service);
                    $this->filesystem->filePut($fileName, $model);

                    $this->console->progress([
                        ' `:collection` collection saved to `:file`',
                        'collection' => $collection,
                        'file' => $fileName]);

                    $pending_fields = [];
                    foreach ($fieldTypes as $field => $type) {
                        if ($type === '' || strpos($type, '|') !== false) {
                            $pending_fields[] = $field;
                        }
                    }

                    if ($pending_fields) {
                        $this->console->warn(['`:collection` has pending fields: :fields',
                            'collection' => $collection,
                            'fields' => implode(', ', $pending_fields)]);
                    }
                }
            }
        }
    }

    /**
     * @param array[] $docs
     *
     * @return array
     */
    protected function _inferFieldTypes($docs)
    {
        $fieldTypes = [];
        foreach ($docs as $doc) {
            foreach ($doc as $field => $value) {
                $fieldTypes[$field][$type = gettype($value)] = 1;
            }
        }

        $r = [];
        foreach ($fieldTypes as $field => $types) {
            unset($types['NULL']);
            if (isset($types['object'])) {
                $r[$field] = 'objectid';
            } else {
                if (count($types) !== 1) {
                    ksort($types);
                }
                $r[$field] = implode('|', array_keys($types));
            }
        }

        return $r;
    }

    /**
     * @param array  $fieldTypes
     * @param string $modelName
     * @param string $service
     * @param bool   $optimized
     *
     * @return string
     */
    protected function _renderModel($fieldTypes, $modelName, $service, $optimized = false)
    {
        $fields = array_keys($fieldTypes);

        $hasPendingType = false;
        foreach ($fieldTypes as $type) {
            if (strpos($type, '|') !== false) {
                $hasPendingType = true;
                break;
            }
        }

        $str = '<?php' . PHP_EOL;
        $str .= 'namespace ' . substr($modelName, 0, strrpos($modelName, '\\')) . ';' . PHP_EOL;
        $str .= PHP_EOL;

        $str .= 'class ' . substr($modelName,
                strrpos($modelName, '\\') + 1) . ' extends \ManaPHP\Mongodb\Model' . PHP_EOL;
        $str .= '{';
        $str .= PHP_EOL;
        foreach ($fieldTypes as $field => $type) {
            if ($field === '_id' && $type === 'objectid') {
                continue;
            }

            $str .= '    public $' . $field . ';' . PHP_EOL;
        }

        if ($service !== 'mongodb') {
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

        if (1) {
            $str .= PHP_EOL;
            $str .= '    /**' . PHP_EOL;
            $str .= '     * @return array' . PHP_EOL;
            $str .= '     */' . PHP_EOL;
            $str .= '    public function getFieldTypes()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= '        return [' . PHP_EOL;
            foreach ($fieldTypes as $field => $type) {
                $str .= "            '$field' => '$type'," . PHP_EOL;
            }
            $str .= '        ];' . PHP_EOL;
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

        if ($optimized && !$hasPendingType) {
            $str .= PHP_EOL;
            $str .= '    /**' . PHP_EOL;
            $str .= '     * @return array' . PHP_EOL;
            $str .= '     */' . PHP_EOL;
            $str .= '    public function getIntTypeFields()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= '        return [' . PHP_EOL;
            foreach ($fieldTypes as $field => $type) {
                if ($type !== 'integer') {
                    continue;
                }

                $str .= "            '$field'," . PHP_EOL;
            }
            $str .= '        ];' . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        $primaryKey = null;
        if ($optimized && ($primaryKey = $this->_inferPrimaryKey($fieldTypes, $modelName))) {
            $str .= PHP_EOL;
            $str .= '    /**' . PHP_EOL;
            $str .= '     * @return string' . PHP_EOL;
            $str .= '     */' . PHP_EOL;
            $str .= '    public function getPrimaryKey()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= "        return '$primaryKey';" . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        if ($optimized && $primaryKey && $fieldTypes[$primaryKey] === 'integer') {
            $str .= PHP_EOL;
            $str .= '    /**' . PHP_EOL;
            $str .= '     * @return string' . PHP_EOL;
            $str .= '     */' . PHP_EOL;
            $str .= '    public function getAutoIncrementField()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= "        return '$primaryKey';" . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        $str .= '}';

        return $str;
    }

    /**
     * @param array  $fieldTypes
     * @param string $modelName
     *
     * @return bool|string
     */
    protected function _inferPrimaryKey($fieldTypes, $modelName)
    {
        if (isset($fieldTypes['id'])) {
            return 'id';
        }

        $plainClass = substr($modelName, strrpos($modelName, '\\'));

        $underscoreClass = Text::underscore($plainClass);
        $tryField = $underscoreClass . '_id';
        if (isset($fieldTypes[$tryField])) {
            return $tryField;
        }

        if ($pos = strrpos($underscoreClass, '_')) {
            $tryField = substr($underscoreClass, $pos + 1) . '_id';

            if (isset($fieldTypes[$tryField])) {
                return $tryField;
            }
        }

        return false;
    }

    /**
     * export mongodb data to csv files
     *
     * @param array  $services           services list
     * @param string $collection_pattern match collection against a pattern
     * @param bool   $bom                contains BOM or not
     */
    public function csvCommand($services = [], $collection_pattern = '', $bom = false)
    {
        foreach ($services ?: $this->_getDbServices() as $service) {
            /**
             * @var \ManaPHP\Mongodb $mongodb
             */
            $mongodb = $this->_di->getShared($service);
            foreach ($this->_getTables($service, $collection_pattern) as $collection) {
                $fileName = "@tmp/mongodb_csv/$service/$collection.csv";

                $this->console->progress(['`:collection` processing...', 'collection' => $collection], '');

                $this->filesystem->dirCreate(dirname($fileName));

                $file = fopen($this->alias->resolve($fileName), 'wb');

                if ($bom) {
                    fprintf($file, "\xEF\xBB\xBF");
                }

                $docs = $mongodb->query($collection);

                if ($docs) {
                    $columns = [];
                    foreach ((array)$docs[0] as $k => $v) {
                        if ($k === '_id' && is_object($v)) {
                            continue;
                        }
                        $columns[] = $k;
                    }

                    fputcsv($file, $columns);
                }

                $linesCount = 0;
                $startTime = microtime(true);
                if (count($docs) !== 0) {
                    foreach ($docs as $doc) {
                        $line = [];
                        foreach ((array)$doc as $k => $v) {
                            if ($k === '_id' && is_object($v)) {
                                continue;
                            }
                            $line[] = $v;
                        }

                        $linesCount++;
                        fputcsv($file, $line);
                    }
                }

                fclose($file);

                $this->console->progress(['write to `:file` success: :count [:time]',
                    'file' => $fileName,
                    'count' => $linesCount,
                    'time' => round(microtime(true) - $startTime, 4)]);
                /** @noinspection DisconnectedForeachInstructionInspection */
            }
        }
    }

    /**
     * list databases and collections
     *
     * @param array  $services           services list
     * @param string $collection_pattern match collection against a pattern
     */
    public function listCommand($services = [], $collection_pattern = '')
    {
        foreach ($services ?: $this->_getDbServices() as $service) {
            /**
             * @var \ManaPHP\DbInterface $mongodb
             */
            $mongodb = $this->_di->getShared($service);

            $this->console->writeLn(['service: `:service`', 'service' => $service], Console::FC_CYAN);
            foreach ($this->_getTables($service, $collection_pattern) as $row => $collection) {
                $docs = $mongodb->query($collection, [], ['limit' => 1]);
                $columns = $docs ? array_keys($docs[0]) : [];

                $this->console->writeLn([' :row :collection(:columns)',
                    'row' => sprintf('%2d ', $row + 1),
                    'collection' => $this->console->colorize($collection, Console::FC_GREEN),
                    'columns' => implode($columns, ', ')]);
            }
        }
    }
}