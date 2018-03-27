<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;
use ManaPHP\Utility\Text;

class MongodbController extends Controller
{
    /**
     * @CliCommand generate model file from base64 encoded string
     * @CliParam   --input:-i the base64 encoded json string
     * @CliParam   --optimized:-o output as more methods as possible (default: 0)
     * @throws \ManaPHP\Cli\Controllers\Exception
     */
    public function modelCommand()
    {
        $input = base64_decode($this->arguments->getOption('input:i'));
        $modelName = $this->arguments->getOption('name:n', 'App\\Models\\Model');
        if (strpos($modelName, '\\') === false) {
            $modelName = 'App\\Models\\' . ucfirst($modelName);
        }

        $fieldTypes = $this->_inferFieldTypes($input);
        $model = $this->_renderModel($fieldTypes, $modelName);
        $file = '@tmp/mongodb/model/' . substr($modelName, strrpos($modelName, '\\') + 1) . '.php';
        $this->filesystem->filePut($file, $model);

        $this->console->writeLn(['write model to :file', 'file' => $file]);
    }

    /**
     * @CliCommand generate models file from data files or online data
     * @CliParam   --service:-s  explicit the mongodb service name
     * @CliParam   --dir the data file directory name
     * @CliParam   --ns namespaces of models
     * @CliParam   --optimized:-o output as more methods as possible (default: 0)
     * @throws \ManaPHP\Cli\Controllers\Exception
     */
    public function modelsCommand()
    {
        $dir = $this->arguments->getOption('dir', '');
        $ns = $this->arguments->getOption('ns', 'App\Models');

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
                $modelClass = $ns . '\\' . $plainClass;

                $model = $this->_renderModel($fieldTypes, $modelClass);

                $this->filesystem->filePut("@tmp/mongodb/models/$plainClass.php", $model);
            }
        } else {
            /**
             * @var \ManaPHP\Mongodb $mongodb
             */
            $mongodb = $this->_di->getShared($this->arguments->getOption('service:s', 'mongodb'));
            foreach ($mongodb->listCollections() as $collection) {
                $docs = $mongodb->query($collection, [], ['limit' => 1]);
                if ($docs) {
                    $plainClass = Text::camelize($collection);
                    $fileName = "@tmp/mongodb/models/$plainClass.php";

                    $this->console->progress(['`:collection` processing...', 'collection' => $collection], '');

                    $fieldTypes = $this->_inferFieldTypes(json_encode($docs[0]));
                    $modelClass = $ns . '\\' . $plainClass;
                    $model = $this->_renderModel($fieldTypes, $modelClass);
                    $this->filesystem->filePut($fileName, $model);

                    $this->console->progress([
                        ' `:model` model for `:collection` collection saved to `:file`',
                        'model' => $plainClass,
                        'collection' => $collection,
                        'file' => $fileName
                    ]);
                }
            }
        }
    }

    /**
     * @param string $str
     *
     * @return array
     * @throws \ManaPHP\Cli\Controllers\Exception
     */
    protected function _inferFieldTypes($str)
    {
        $json = json_decode('[' . $str . ']', true);
        if (!$json) {
            throw new Exception('not json');
        }
        $fieldTypes = [];

        foreach ((array)$json[0] as $k => $v) {
            if ($v === null) {
                $fieldTypes[$k] = 'string|int';
            } elseif (is_array($v)) {
                if (isset($v['$oid'])) {
                    $fieldTypes[$k] = 'objectid';
                } else {
                    throw new Exception(['unsupported `:data` data expression for `:property` property', 'data' => $v, 'propery' => $k]);
                }
            } elseif (is_int($v)) {
                $fieldTypes[$k] = 'integer';
            } elseif (is_string($v)) {
                $fieldTypes[$k] = 'string';
            } elseif (is_float($v)) {
                $fieldTypes[$k] = 'float';
            } else {
                throw new Exception(['unsupported `:data` data expression for `:property` property', 'data' => $v, 'propery' => $k]);
            }
        }

        return $fieldTypes;
    }

    /**
     * @param array  $fieldTypes
     * @param string $modelName
     *
     * @return string
     */
    protected function _renderModel($fieldTypes, $modelName)
    {
        $optimized = $this->arguments->getOption('optimized:o', 0);

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

        $str .= 'class ' . substr($modelName, strrpos($modelName, '\\') + 1) . ' extends \ManaPHP\Mongodb\Model' . PHP_EOL;
        $str .= '{';
        $str .= PHP_EOL;
        foreach ($fieldTypes as $field => $type) {
            if ($field === '_id' && $type === 'objectid') {
                continue;
            }

            $str .= '    public $' . $field . ';' . PHP_EOL;
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
     * @CliCommand export mongodb data to csv files
     * @CliParam   --service:-s  explicit the mongodb service name
     * @CliParam   --collection:-c export these collections only
     * @CliParam   --bom  contains BOM or not (default: 0)
     */
    public function csvCommand()
    {
        /**
         * @var \ManaPHP\Mongodb $mongodb
         */
        $mongodb = $this->_di->getShared($this->arguments->getOption('service:s', 'mongodb'));

        $bom = $this->arguments->getOption('bom', 0);
        $collections = $this->arguments->getOption('collection:c', '');

        foreach ($mongodb->listCollections() as $collection) {
            if ($collections && strpos($collections, $collection) === false) {
                continue;
            }

            $fileName = "@tmp/mongodb/csv/$collection.csv";

            $this->console->writeLn(['`:collection` processing...', 'collection' => $collection]);

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

            $this->console->writeLn(['write to `:file` success: :count [:time]', 'file' => $fileName, 'count' => $linesCount, 'time' => round(microtime(true) - $startTime, 4)]);
            /** @noinspection DisconnectedForeachInstructionInspection */
            $this->console->writeLn();
        }
    }

    /**
     * @CliCommand list databases and collections
     * @CliParam   --service:-s  explicit the mongodb service name
     * @CliParam   --database:-d list collections in these database only
     */
    public function listCommand()
    {
        /**
         * @var \ManaPHP\Mongodb $mongodb
         */
        $mongodb = $this->_di->getShared($this->arguments->getOption('service:s', 'mongodb'));

        $databases = $mongodb->listDatabases();
        $filterDatabases = $this->arguments->getOption('database:d', '');
        sort($databases);
        foreach ($databases as $database) {
            if ($filterDatabases && strpos($filterDatabases, $database) === false) {
                continue;
            }
            $collections = $mongodb->listCollections($database);
            sort($collections);
            $this->console->writeLn([':database[:count]', 'database' => $database, 'count' => count($collections)]);
            foreach ($collections as $collection) {
                $this->console->writeLn(['    :collection', 'collection' => $collection]);
            }
        }
    }
}