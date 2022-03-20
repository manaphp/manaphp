<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Cli\Console;
use ManaPHP\Data\Db;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;

/**
 * @property-read \ManaPHP\ConfigInterface $config
 * @property-read \ManaPHP\AliasInterface  $alias
 */
class DbCommand extends Command
{
    /**
     * @return array
     */
    protected function getDbServices(): array
    {
        $services = [];
        foreach ($this->config->get('dependencies') as $service => $config) {
            $config = json_stringify($config);
            if (preg_match('#(mysql|mssql|sqlite)://#', $config)) {
                $services[] = $service;
            }
        }
        return $services;
    }

    /**
     * @param string  $service
     * @param ?string $pattern
     *
     * @return array
     */
    protected function getTables(string $service, ?string $pattern = null): array
    {
        /** @var \ManaPHP\Data\DbInterface $db */
        $db = $this->container->get($service);
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
    protected function getConstantsByFile(string $modelName): string
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
    protected function getConstantsByDb(string $service, string $table): string
    {
        static $cached;

        if (!isset($cached[$service])) {
            /** @var \ManaPHP\Data\DbInterface $db */
            $db = $this->container->get($service);
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
     * @param string $class
     * @param string $table
     * @param bool   $optimized
     * @param bool   $camelized
     *
     * @return string
     */
    protected function renderModel(string $service, string $class, string $table, bool $optimized = false,
        bool $camelized = false
    ): string {
        /** @var Db $db */
        $db = $this->container->get($service);
        $metadata = $db->getMetadata($table);

        $fields = (array)$metadata[Db::METADATA_ATTRIBUTES];

        $pos = strrpos($class, '\\');
        $plainClass = substr($class, $pos + 1);
        $namespace = substr($class, 0, $pos);

        if ($constants = $this->getConstantsByDb($service, $table)) {
            null;
        } elseif ($constants = $this->getConstantsByFile($plainClass)) {
            $constants = '    ' . $constants;
        }

        $str = '<?php' . PHP_EOL;
        $str .= 'declare(strict_types=1);' . PHP_EOL . PHP_EOL;
        $str .= "namespace $namespace;" . PHP_EOL;
        $str .= PHP_EOL;

        if (strpos($class, '\\Areas\\')) {
            $str .= 'use App\Models\Model;' . PHP_EOL;
            $str .= PHP_EOL;
        }

        $str .= 'class ' . $plainClass . ' extends Model' . PHP_EOL;
        $str .= '{';
        if ($constants) {
            $str .= PHP_EOL . $constants . PHP_EOL;
        }

        $str .= PHP_EOL;
        foreach ($fields as $field) {
            $field = $camelized ? Str::camelize($field) : $field;
            $str .= '    public $' . $field . ';' . PHP_EOL;
        }

        if ($service !== 'db') {
            $str .= PHP_EOL;
            $str .= '    public function db(): string' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= "        return '$service';" . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        $pos = strrpos($table, '_');
        if ($optimized || ($pos !== false && strrpos($table, '_', $pos - 1) !== false)) {
            $str .= PHP_EOL;
            $str .= '    public function table(): string' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= "        return '$table';" . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        if ($optimized) {
            $str .= PHP_EOL;
            $str .= '    public function fields(): array' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= '        return [' . PHP_EOL;
            foreach ($fields as $field) {
                $field = $camelized ? Str::camelize($field) : $field;
                $str .= "            '$field'," . PHP_EOL;
            }
            $str .= '        ];' . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        if ($camelized) {
            $str .= PHP_EOL;
            $str .= '    public function map(): array' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= '        return [' . PHP_EOL;
            foreach ($fields as $field) {
                $t = Str::camelize($field);
                if ($t !== $field) {
                    $str .= "            '$t' => '$field'," . PHP_EOL;
                }
            }
            $str .= '        ];' . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        $primaryKeys = $metadata[Db::METADATA_PRIMARY_KEY];
        if ($primaryKey = count($primaryKeys) === 1 ? $primaryKeys[0] : null) {
            if ($optimized
                || ($primaryKey !== 'id' && $primaryKey !== $table . '_id' && $primaryKey !== $table . 'Id')
            ) {
                $str .= PHP_EOL;
                $str .= '    public function primaryKey(): string' . PHP_EOL;
                $str .= '    {' . PHP_EOL;
                $str .= "        return '$primaryKey';" . PHP_EOL;
                $str .= '    }' . PHP_EOL;
            }

        } else {
            $str .= PHP_EOL;
            $str .= '    public function primaryKey(): string' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= "        return '???';" . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        $autoIncField = $metadata[Db::METADATA_AUTO_INCREMENT_KEY];
        if ($optimized) {
            $str .= PHP_EOL;
            $str .= '    public function autoIncrementField(): ?string' . PHP_EOL;
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
            $str .= '    public function intFields(): array' . PHP_EOL;
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
     * @param string $service
     * @param string $table
     * @param string $rootNamespace
     *
     * @return string
     */
    protected function renderTable(string $service, string $table, string $rootNamespace = 'App\Models'): string
    {
        $plainClass = Str::pascalize($table);
        $modelName = $rootNamespace . '\\' . $plainClass;

        if ($constants = $this->getConstantsByDb($service, $table)) {
            null;
        } elseif ($constants = $this->getConstantsByFile($plainClass)) {
            $constants = '    ' . $constants;
        }

        $str = '<?php' . PHP_EOL . PHP_EOL;
        $str .= 'namespace ' . substr($modelName, 0, strrpos($modelName, '\\')) . ';' . PHP_EOL;
        $str .= PHP_EOL;

        $str .= 'class ' . $plainClass . ' extends Table' . PHP_EOL;
        $str .= '{';
        if ($constants) {
            $str .= PHP_EOL . $constants . PHP_EOL;
        }

        if ($service !== 'db') {
            $str .= PHP_EOL;
            $str .= '    public function db()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= "        return '$service';" . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        if (true) {
            $str .= PHP_EOL;
            $str .= '    public function table()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= "        return '$table';" . PHP_EOL;
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
     *
     * @return void
     */
    public function listAction(array $services = [], string $table_pattern = ''): void
    {
        foreach ($services ?: $this->getDbServices() as $service) {
            /** @var \ManaPHP\Data\DbInterface $db */
            $db = $this->container->get($service);

            $this->console->writeLn(['service: `:service`', 'service' => $service], Console::FC_CYAN);
            foreach ($this->getTables($service, $table_pattern) as $row => $table) {
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
     * @return array
     */
    protected function getAreas(): array
    {
        $areas = [];
        foreach (LocalFS::glob('@app/Areas/*', GLOB_ONLYDIR) as $item) {
            $area = basename($item);
            if (!in_array($area, ['Api', 'Admin', 'User'])) {
                $areas[] = $area;
            }
        }

        return $areas;
    }

    /**
     * generate model file in online
     *
     * @param string $table     table name
     * @param string $service   service name
     * @param bool   $optimized output as more methods as possible
     * @param bool   $camelized
     *
     * @return void
     */
    public function modelAction(string $table, string $service = '', bool $optimized = false, bool $camelized = false
    ): void {
        /** @var \ManaPHP\Data\DbInterface $db */
        if ($service) {
            $db = $this->container->get($service);
            if (!in_array($table, $db->getTables(), true)) {
                throw new Exception(['`:table` is not exists', 'table' => $table]);
            }
        } else {
            foreach ($this->getDbServices() as $s) {
                $db = $this->container->get($s);
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

        $plainClass = Str::pascalize($table);
        $fileName = "@runtime/db_model/$plainClass.php";
        $class = "App\Models\\$plainClass";
        $model_str = $this->renderModel($service, $class, $table, $optimized, $camelized);
        LocalFS::filePut($fileName, $model_str);

        $this->console->progress(['`:table` table saved to `:file`', 'table' => $table, 'file' => $fileName]);
    }

    /**
     * generate models file in online
     *
     * @param array  $services      services name list
     * @param string $table_pattern match table against a pattern
     * @param bool   $optimized     output as more methods as possible
     * @param bool   $camelized
     *
     * @return void
     */
    public function modelsAction(array $services = [], string $table_pattern = '', bool $optimized = false,
        bool $camelized = false
    ): void {
        $areas = $this->getAreas();

        foreach ($services ?: $this->getDbServices() as $service) {
            foreach ($this->getTables($service, $table_pattern) as $table) {
                $this->console->progress(['`:table` processing...', 'table' => $table], '');
                $plainClass = Str::pascalize($table);
                $class = "App\Models\\$plainClass";
                $fileName = "@runtime/db_models/$plainClass.php";
                if (($pos = strpos($table, '_')) !== false) {
                    $area = Str::pascalize(substr($table, 0, $pos));
                    if (in_array($area, $areas, true)) {
                        $plainClass = Str::pascalize(substr($table, $pos + 1));
                        $class = 'App\\Areas\\Models\\' . Str::pascalize(substr($table, $pos));
                        $fileName = "@runtime/db_models/Areas/$area/$plainClass.php";
                    }
                }

                $model_str = $this->renderModel($service, $class, $table, $optimized, $camelized);
                LocalFS::filePut($fileName, $model_str);

                $this->console->progress(['  `:table` table saved to `:file`', 'table' => $table, 'file' => $fileName]);
            }
        }
    }

    /**
     * generate models file in online
     *
     * @param array  $services      services name list
     * @param string $table_pattern match table against a pattern
     * @param string $namespace     namespace of models
     *
     * @return void
     */
    public function tablesAction(array $services = [], string $table_pattern = '', string $namespace = 'App\Tables'
    ): void {
        if (!str_contains($namespace, '\\')) {
            $namespace = 'App\\' . ucfirst($namespace) . '\\Tables';
        }

        foreach ($services ?: $this->getDbServices() as $service) {
            foreach ($this->getTables($service, $table_pattern) as $table) {
                $this->console->progress(['`:table` processing...', 'table' => $table], '');

                $plainClass = Str::pascalize($table);
                $fileName = "@runtime/db_tables/$plainClass.php";
                $model_str = $this->renderTable($service, $table, $namespace);
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
     *
     * @return void
     */
    public function jsonAction(array $services = [], string $table_pattern = ''): void
    {
        foreach ($services ?: $this->getDbServices() as $service) {
            /** @var \ManaPHP\Data\DbInterface $db */
            $db = $this->container->get($service);
            foreach ($this->getTables($service, $table_pattern) as $table) {
                $fileName = "@runtime/db_json/$service/$table.json";

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
     *
     * @return void
     */
    public function csvAction(array $services = [], string $table_pattern = '', bool $bom = false): void
    {
        foreach ($services ?: $this->getDbServices() as $service) {
            /** @var \ManaPHP\Data\Db $db */
            $db = $this->container->get($service);
            foreach ($this->getTables($service, $table_pattern) as $table) {
                $this->console->progress(['`:table` processing...', 'table' => $table], '');

                $fileName = "@runtime/db_csv/$service/$table.csv";
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