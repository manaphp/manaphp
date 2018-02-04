<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;
use ManaPHP\Utility\Text;

class MongodbController extends Controller
{
    /**
     * @CliCommand generate model file from base64 encoded string
     * @CliParam   --input:-i the base64 encoded json string
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
        $model = $this->_genModel($fieldTypes, $modelName);
        $file = '@data/tmp/mongodb/model/' . substr($modelName, strrpos($modelName, '\\') + 1) . '.php';
        $this->filesystem->filePut($file, $model);

        $this->console->writeLn('write model to :file', ['file' => $file]);
    }

    /**
     * @CliCommand generate models file from data files or online data
     * @CliParam   --service:-s  explicit the mongodb service name
     * @CliParam   --dir the data file directory name
     * @throws \ManaPHP\Cli\Controllers\Exception
     */
    public function modelsCommand()
    {
        $dir = $this->arguments->getOption('dir');
        if (!$this->filesystem->dirExists($dir)) {
            throw new Exception('`:dir` dir is not exists', ['dir' => $dir]);
        }
        $ns = $this->arguments->getOption('ns', 'App\Models');

        foreach ($this->filesystem->glob($dir . '/*.json') as $file) {
            $lines = file($file);
            if (!isset($lines[0])) {
                continue;
            }
            $fieldTypes = $this->_inferFieldTypes($lines[0]);
            $fileName = basename($file, '.json');
            $plainClass = Text::camelize($fileName);
            $modelClass = $ns . '\\' . $plainClass;

            $model = $this->_genModel($fieldTypes, $modelClass);

            $this->filesystem->filePut("@data/tmp/mongodb/models/$plainClass.php", $model);
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
                    throw new Exception('unsupported `:data` data expression for `:property` property', ['data' => $v, 'propery' => $k]);
                }
            } elseif (is_int($v)) {
                $fieldTypes[$k] = 'integer';
            } elseif (is_string($v)) {
                $fieldTypes[$k] = 'string';
            } elseif (is_float($v)) {
                $fieldTypes[$k] = 'float';
            } else {
                throw new Exception('unsupported `:data` data expression for `:property` property', ['data' => $v, 'propery' => $k]);
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
    protected function _genModel($fieldTypes, $modelName)
    {
        $optimized = $this->arguments->hasOption('optimized');

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
            $str .= '    public $' . $field . ';' . PHP_EOL;
        }

        if (1) {
            $str .= PHP_EOL;
            $str .= '    /**' . PHP_EOL;
            $str .= '     * @return array' . PHP_EOL;
            $str .= '     */' . PHP_EOL;
            $str .= '    public static function getFieldTypes()' . PHP_EOL;
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
            $str .= '    public static function getFields()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= '        return [' . PHP_EOL;
            foreach ($fieldTypes as $field => $type) {
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
            $str .= '    public static function getIntTypeFields()' . PHP_EOL;
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
            $str .= '    public static function getPrimaryKey()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= "        return '$primaryKey';" . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        if ($optimized && $primaryKey && $fieldTypes[$primaryKey] === 'integer') {
            $str .= PHP_EOL;
            $str .= '    /**' . PHP_EOL;
            $str .= '     * @return string' . PHP_EOL;
            $str .= '     */' . PHP_EOL;
            $str .= '    public static function getAutoIncrementField()' . PHP_EOL;
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
}