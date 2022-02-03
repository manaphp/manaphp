<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Console;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;

/**
 * @property-read \ManaPHP\ConfigInterface $config
 * @property-read \ManaPHP\AliasInterface  $alias
 */
class MongodbCommand extends \ManaPHP\Cli\Command
{
    /**
     * @param array $services
     *
     * @return array
     */
    protected function getServices(array $services): array
    {
        if ($services) {
            $container = $this->container;

            foreach ($services as $index => $service) {
                if (!$container->has($service)) {
                    if ($container->has($service . 'Mongodb')) {
                        $services[$index] = $service . 'Mongodb';
                    } elseif ($container->has($service . '_mongodb')) {
                        $services[$index] = $service . '_mongodb';
                    } else {
                        $this->console->warning(['`:service` service is not exists: ignoring', 'service' => $service]);
                        unset($services[$index]);
                    }
                }
            }
        } else {
            $services = [];
            foreach ($this->config->get('dependencies') as $service => $config) {
                $config = json_stringify($config);
                if (str_contains($config, 'mongodb://')) {
                    $services[] = $service;
                }
            }
        }

        return $services;
    }

    /**
     * generate model file from base64 encoded string
     *
     * @param string $input     the base64 encoded json string
     * @param string $modelName
     * @param bool   $optimized output as more methods as possible
     *
     * @return void
     */
    public function modelAction(string $input, string $modelName, bool $optimized = false): void
    {
        if (!str_contains($modelName, '\\')) {
            $modelName = 'App\\Models\\' . ucfirst($modelName);
        }

        $fieldTypes = $this->inferFieldTypes([json_parse($input)]);

        $model = $this->renderModel($fieldTypes, $modelName, 'mongodb', '', $optimized);
        $file = '@tmp/mongodb_model/' . substr($modelName, strrpos($modelName, '\\') + 1) . '.php';
        LocalFS::filePut($file, $model);

        $this->console->writeLn(['write model to :file', 'file' => $file]);
    }

    /**
     * generate models file from data files or online data
     *
     * @param array $services  explicit the mongodb service name
     * @param bool  $optimized output as more methods as possible
     * @param int   $sample    sample size
     * @param array $db        db name list
     *
     * @return void
     * @throws \ManaPHP\Data\Mongodb\Exception
     */
    public function modelsAction(
        array $services = [],
        bool $optimized = false,
        int $sample = 1000,
        array $db = []
    ): void {
        foreach ($this->getServices($services) as $service) {
            /** @var \ManaPHP\Data\Mongodb $mongodb */
            $mongodb = $this->container->get($service);

            $defaultDb = $mongodb->getDb();
            $dbs = $defaultDb ? [$defaultDb] : $mongodb->listDatabases();
            foreach ($dbs as $cdb) {
                if (in_array($cdb, ['admin', 'local'], true) || ($db && !in_array($cdb, $db, true))) {
                    continue;
                }

                foreach ($mongodb->listCollections($cdb) as $collection) {
                    if (str_contains($collection, '.')) {
                        continue;
                    }

                    if (!$docs = $mongodb->aggregate("$cdb.$collection", [['$sample' => ['size' => $sample]]])) {
                        continue;
                    }

                    $plainClass = Str::pascalize($collection);
                    $fileName = "@tmp/mongodb_models/$plainClass.php";

                    $this->console->progress(['`:collection` processing...', 'collection' => $collection], '');

                    $fieldTypes = $this->inferFieldTypes($docs);
                    $modelClass = 'App\Models\\' . $plainClass;
                    $ns = $defaultDb ? $collection : "$cdb.$collection";
                    $model = $this->renderModel($fieldTypes, $modelClass, $service, $ns, $optimized);
                    LocalFS::filePut($fileName, $model);

                    $this->console->progress(
                        [
                            ' `:namespace` collection saved to `:file`',
                            'namespace' => "$cdb.$collection",
                            'file'      => $fileName
                        ]
                    );

                    $pending_fields = [];
                    foreach ($fieldTypes as $field => $type) {
                        if ($type === '' || str_contains($type, '|')) {
                            $pending_fields[] = $field;
                        }
                    }

                    if ($pending_fields) {
                        $this->console->warning(
                            [
                                '`:collection` has pending fields: :fields',
                                'collection' => $collection,
                                'fields'     => implode(', ', $pending_fields)
                            ]
                        );
                    }
                }
            }
        }
    }

    /**
     * @param array[] $docs
     *
     * @return string[]
     */
    protected function inferFieldTypes(array $docs): array
    {
        $fieldTypes = [];
        foreach ($docs as $doc) {
            foreach ($doc as $field => $value) {
                $fieldTypes[$field][gettype($value)] = 1;
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
     * @param string $modelName
     *
     * @return string
     */
    protected function getConstants(string $modelName): string
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
     * @param array  $fieldTypes
     * @param string $modelName
     * @param string $service
     * @param string $namespace
     * @param bool   $optimized
     *
     * @return string
     */
    protected function renderModel(array $fieldTypes, string $modelName, string $service, string $namespace,
        bool $optimized = false
    ): string {
        $fields = array_keys($fieldTypes);

        $hasPendingType = false;
        foreach ($fieldTypes as $type) {
            if (str_contains($type, '|')) {
                $hasPendingType = true;
                break;
            }
        }

        $constants = $this->getConstants($modelName);

        $str = '<?php' . PHP_EOL;
        $str .= 'namespace ' . substr($modelName, 0, strrpos($modelName, '\\')) . ';' . PHP_EOL;
        $str .= PHP_EOL;

        $str .= 'class ' . substr(
                $modelName,
                strrpos($modelName, '\\') + 1
            ) . ' extends \ManaPHP\Data\Mongodb\Model' . PHP_EOL;
        $str .= '{';
        if ($constants) {
            $str .= PHP_EOL . '    ' . $constants . PHP_EOL;
        }

        $str .= PHP_EOL;
        foreach ($fieldTypes as $field => $type) {
            if ($field === '_id' && $type === 'objectid') {
                continue;
            }

            $str .= '    public $' . $field . ';' . PHP_EOL;
        }

        if (true) {
            $str .= PHP_EOL;
            $str .= '    public function fieldTypes()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= '        return [' . PHP_EOL;
            foreach ($fieldTypes as $field => $type) {
                if ($field === '_id' && $type === 'objectid') {
                    continue;
                }
                $str .= "            '$field' => '$type'," . PHP_EOL;
            }
            $str .= '        ];' . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        if ($service !== 'mongodb') {
            $str .= PHP_EOL;
            $str .= '    public function db()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= "        return '$service';" . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        if ($namespace) {
            $source = ($pos = strpos($namespace, '.')) ? substr($namespace, $pos + 1) : $namespace;
            $str .= PHP_EOL;
            $str .= '    public function table()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= "        return '$source';" . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        if ($primaryKey = $this->inferPrimaryKey($fieldTypes, $modelName)) {
            $str .= PHP_EOL;
            $str .= '    public function primaryKey()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= "        return '$primaryKey';" . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        if ($optimized && $primaryKey && $fieldTypes[$primaryKey] === 'int') {
            $str .= PHP_EOL;
            $str .= '    public function autoIncrementField()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= "        return '$primaryKey';" . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        if ($optimized) {
            $str .= PHP_EOL;
            $str .= '    public function fields()' . PHP_EOL;
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
            $str .= '    public function intFields()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= '        return [' . PHP_EOL;
            foreach ($fieldTypes as $field => $type) {
                if ($type !== 'int') {
                    continue;
                }

                $str .= "            '$field'," . PHP_EOL;
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
     * @return false|string
     */
    protected function inferPrimaryKey(array $fieldTypes, string $modelName): false|string
    {
        if (isset($fieldTypes['id'])) {
            return 'id';
        }

        $plainClass = substr($modelName, strrpos($modelName, '\\'));

        $underscoreClass = Str::snakelize($plainClass);
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
     *
     * @return void
     */
    public function csvAction(array $services = [], string $collection_pattern = '', bool $bom = false): void
    {
        foreach ($this->getServices($services) as $service) {
            /** @var \ManaPHP\Data\Mongodb $mongodb */
            $mongodb = $this->container->get($service);

            $dbs = $mongodb->getDb() ? [$mongodb->getDb()] : $mongodb->listDatabases();
            foreach ($dbs as $db) {
                if (in_array($db, ['admin', 'local'], true)) {
                    continue;
                }

                foreach ($mongodb->listCollections($db) as $collection) {
                    if ($collection_pattern && !fnmatch($collection_pattern, $collection)) {
                        continue;
                    }

                    $fileName = "@tmp/mongodb_csv/$db/$collection.csv";

                    $this->console->progress(['`:collection` processing...', 'collection' => $collection], '');

                    LocalFS::dirCreate(dirname($fileName));

                    $file = fopen($this->alias->resolve($fileName), 'wb');

                    if ($bom) {
                        fprintf($file, "\xEF\xBB\xBF");
                    }

                    $docs = $mongodb->fetchAll("$db.$collection");

                    if ($docs) {
                        $columns = [];
                        foreach ($docs[0] as $k => $v) {
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
                            foreach ($doc as $k => $v) {
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

                    $this->console->progress(
                        [
                            'write to `:file` success: :count [:time]',
                            'file'  => $fileName,
                            'count' => $linesCount,
                            'time'  => round(microtime(true) - $startTime, 4)
                        ]
                    );
                }
            }
        }
    }

    /**
     * list databases and collections
     *
     * @param array  $services           services list
     * @param string $collection_pattern match collection against a pattern
     * @param string $field              collection must contain one this field
     * @param array  $db
     *
     * @return void
     */
    public function listAction(array $services = [], string $collection_pattern = '', string $field = '', array $db = []
    ): void {
        foreach ($this->getServices($services) as $service) {
            /** @var \ManaPHP\Data\Mongodb $mongodb */
            $mongodb = $this->container->get($service);

            $dbs = $mongodb->getDb() ? [$mongodb->getDb()] : $mongodb->listDatabases();
            foreach ($dbs as $cdb) {
                if ($db && !in_array($cdb, $db, true)) {
                    continue;
                }

                $this->console->writeLn(
                    ['---`:db` db of `:service` service---', 'db' => $cdb, 'service' => $service],
                    Console::BC_CYAN
                );
                foreach ($mongodb->listCollections($cdb) as $row => $collection) {
                    if ($collection_pattern && !fnmatch($collection_pattern, $collection)) {
                        continue;
                    }
                    if ($field) {
                        if (!$docs = $mongodb->fetchAll(
                            "$cdb.$collection", [$field => ['$exists' => 1]],
                            ['limit' => 1]
                        )
                        ) {
                            continue;
                        }
                    } else {
                        $docs = $mongodb->fetchAll("$cdb.$collection", [], ['limit' => 1]);
                    }
                    $columns = $docs ? array_keys($docs[0]) : [];

                    $this->console->writeLn(
                        [
                            ' :row :namespace(:columns)',
                            'row'       => sprintf('%2d ', $row + 1),
                            'namespace' => $this->console->colorize("$cdb.$collection", Console::FC_GREEN),
                            'columns'   => implode(', ', $columns)
                        ]
                    );
                }
            }
        }
    }
}