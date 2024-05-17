<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\AliasInterface;
use ManaPHP\Cli\Command;
use ManaPHP\Cli\Console;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\ConfigInterface;
use ManaPHP\Di\Pool;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;
use ManaPHP\Mongodb\MongodbConnectorInterface;
use ManaPHP\Mongodb\MongodbInterface;
use Psr\Container\ContainerInterface;
use function count;
use function dirname;
use function gettype;
use function in_array;
use function is_object;

class MongodbCommand extends Command
{
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected AliasInterface $alias;
    #[Autowired] protected MongodbConnectorInterface $connector;
    #[Autowired] protected ConfigInterface $config;

    /**
     * @return array
     */
    protected function getConnections(): array
    {
        $connections = [];
        /** @var Pool $mongodb */
        $mongodb = $this->config->get(MongodbInterface::class);
        foreach ($mongodb->pool ?? [] as $connection => $config) {
            $config = json_stringify($config);
            if (str_contains($config, 'mongodb://')) {
                $connections[] = $connection;
            }
        }

        return $connections;
    }

    /**
     * generate entity file from base64 encoded string
     *
     * @param string $input the base64 encoded json string
     * @param string $entityClass
     *
     * @return void
     */
    public function entityAction(string $input, string $entityClass): void
    {
        if (!str_contains($entityClass, '\\')) {
            $entityClass = 'App\\Entities\\' . ucfirst($entityClass);
        }

        $fieldTypes = $this->inferFieldTypes([json_parse($input)]);

        $entity = $this->renderEntity($fieldTypes, $entityClass);
        $file = '@runtime/mongodb_entities/' . substr($entityClass, strrpos($entityClass, '\\') + 1) . '.php';
        LocalFS::filePut($file, $entity);

        $this->console->writeLn("write entity to `:$file`");
    }

    /**
     * generate entities file from data files or online data
     *
     * @param int   $sample sample size
     * @param array $db     db name list
     *
     * @return void
     */
    public function entitiesAction(
        int $sample = 1000,
        array $db = []
    ): void {
        foreach ($this->getConnections() as $connection) {
            $mongodb = $this->connector->get($connection);

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
                    $fileName = "@runtime/mongodb_entities/$plainClass.php";

                    $fieldTypes = $this->inferFieldTypes($docs);
                    $entityClass = 'App\Entities\\' . $plainClass;
                    $entity = $this->renderEntity($fieldTypes, $entityClass);
                    LocalFS::filePut($fileName, $entity);

                    $this->console->writeLn(sprintf(' `%s` collection saved to `%s`', "$cdb.$collection", $fileName));

                    $pending_fields = [];
                    foreach ($fieldTypes as $field => $type) {
                        if ($type === '' || str_contains($type, '|')) {
                            $pending_fields[] = $field;
                        }
                    }

                    if ($pending_fields) {
                        $this->console->warning(
                            sprintf('`%s` has pending fields: `%s`', $collection, implode(', ', $pending_fields))
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
     * @param string $entityName
     *
     * @return string
     */
    protected function getConstants(string $entityName): string
    {
        $file = "@app/Entities/$entityName.php";
        if (!LocalFS::fileExists($file)) {
            return '';
        }

        $constants = '';
        foreach (file($this->alias->resolve($file)) as $line) {
            if (preg_match('#^\s+const\s+\w+\s*=#', $line) === 1) {
                $constants .= $line;
            } elseif (trim($line) === '') {
                $constants .= PHP_EOL;
            }
        }

        return trim($constants);
    }

    /**
     * @param array  $fieldTypes
     * @param string $entityName
     *
     * @return string
     */
    protected function renderEntity(array $fieldTypes, string $entityName): string
    {
        $constants = $this->getConstants($entityName);

        $str = '<?php' . PHP_EOL;
        $str .= 'namespace ' . substr($entityName, 0, strrpos($entityName, '\\')) . ';' . PHP_EOL;
        $str .= PHP_EOL;

        $str .= 'class ' . substr(
                $entityName,
                strrpos($entityName, '\\') + 1
            ) . ' extends \ManaPHP\Entities\Entity' . PHP_EOL;
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

        $str .= '}';

        return $str;
    }

    /**
     * export mongodb data to csv files
     *
     * @param string $collection_pattern match collection against a pattern
     * @param bool   $bom                contains BOM or not
     *
     * @return void
     */
    public function csvAction(string $collection_pattern = '', bool $bom = false): void
    {
        foreach ($this->getConnections() as $connection) {
            $mongodb = $this->connector->get($connection);

            $dbs = $mongodb->getDb() ? [$mongodb->getDb()] : $mongodb->listDatabases();
            foreach ($dbs as $db) {
                if (in_array($db, ['admin', 'local'], true)) {
                    continue;
                }

                foreach ($mongodb->listCollections($db) as $collection) {
                    if ($collection_pattern && !fnmatch($collection_pattern, $collection)) {
                        continue;
                    }

                    $fileName = "@runtime/mongodb_csv/$db/$collection.csv";

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

                    $this->console->writeLn(
                        sprintf(
                            'write to `%s` success: %d [%f]', $fileName, $linesCount,
                            round(microtime(true) - $startTime, 4)
                        )
                    );
                }
            }
        }
    }

    /**
     * list databases and collections
     *
     * @param string $collection_pattern match collection against a pattern
     * @param string $field              collection must contain one this field
     * @param array  $db
     *
     * @return void
     */
    public function listAction(string $collection_pattern = '', string $field = '',
        array $db = []
    ): void {
        foreach ($this->getConnections() as $connection) {
            $mongodb = $this->connector->get($connection);

            $dbs = $mongodb->getDb() ? [$mongodb->getDb()] : $mongodb->listDatabases();
            foreach ($dbs as $cdb) {
                if ($db && !in_array($cdb, $db, true)) {
                    continue;
                }

                $this->console->writeLn("---`$cdb` db of `$connection` connection---", [], Console::BC_CYAN);
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
                        sprintf(
                            ' %2d %s(%s)',
                            $row + 1,
                            $this->console->colorize("$cdb.$collection", Console::FC_GREEN),
                            implode(', ', $columns)
                        )
                    );
                }
            }
        }
    }
}