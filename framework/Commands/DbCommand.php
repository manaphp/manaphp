<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\AliasInterface;
use ManaPHP\Cli\Command;
use ManaPHP\Cli\Console;
use ManaPHP\ConfigInterface;
use ManaPHP\Data\Db;
use ManaPHP\Data\DbInterface;
use ManaPHP\Data\Model\Attribute\ColumnMap;
use ManaPHP\Data\Model\Attribute\Connection;
use ManaPHP\Data\Model\Attribute\PrimaryKey;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;

class DbCommand extends Command
{
    #[Inject]
    protected ConfigInterface $config;
    #[Inject]
    protected AliasInterface $alias;
    #[Inject]
    protected Db\FactoryInterface $dbFactory;

    protected array $tableConstants = [];

    /**
     * @return array
     */
    protected function getConnections(): array
    {
        return array_keys($this->config->get('factories', [])[DbInterface::class] ?? []);
    }

    /**
     * @param string  $connection
     * @param ?string $pattern
     *
     * @return array
     */
    protected function getTables(string $connection, ?string $pattern = null): array
    {
        $db = $this->dbFactory->get($connection);
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
            if (preg_match('#^\s+const\s+[A-Z\d_]+\s*=#', $line) === 1) {
                $constants .= $line;
            } elseif (trim($line) === '') {
                $constants .= PHP_EOL;
            }
        }

        return trim($constants);
    }

    /**
     * @param string $connection
     * @param string $table
     *
     * @return string
     */
    protected function getConstantsByDb(string $connection, string $table): string
    {
        if (!isset($this->tableConstants[$connection])) {
            $db = $this->dbFactory->get($connection);
            $metadata_table = 'metadata_constant';
            if (!in_array($metadata_table, $db->getTables(), true)) {
                $this->tableConstants[$connection] = [];
            } else {
                $metadata_table = $db->getPrefix() . $metadata_table;
                $rows = $db->fetchAll(/**@lang text */ "SELECT `id`, `constants` FROM $metadata_table");
                foreach ($rows as $row) {
                    $this->tableConstants[$connection][$row['id']] = $row['constants'];
                }
            }
        }

        if (!isset($this->tableConstants[$connection][$table])) {
            return '';
        }

        $lines = [];
        $constants = preg_split('#[\r\n]{1,2}#m', trim($this->tableConstants[$connection][$table]));
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
     * @param string $connection
     * @param string $class
     * @param string $table
     * @param bool   $optimized
     * @param bool   $camelized
     *
     * @return string
     */
    protected function renderModel(string $connection, string $class, string $table, bool $optimized = false,
        bool $camelized = false
    ): string {
        $db = $this->dbFactory->get($connection);
        $metadata = $db->getMetadata($table);

        $fields = (array)$metadata[Db::METADATA_ATTRIBUTES];

        $pos = strrpos($class, '\\');
        $plainClass = substr($class, $pos + 1);
        $namespace = substr($class, 0, $pos);

        if ($constants = $this->getConstantsByDb($connection, $table)) {
            null;
        } elseif ($constants = $this->getConstantsByFile($plainClass)) {
            $constants = '    ' . $constants;
        }

        $str = '<?php' . PHP_EOL;
        $str .= 'declare(strict_types=1);' . PHP_EOL . PHP_EOL;
        $str .= "namespace $namespace;" . PHP_EOL;
        $str .= PHP_EOL;

        $uses = [];
        if (strpos($class, '\\Areas\\')) {
            $uses[] = 'App\Models\Model';
        }

        $attributes = [];
        if ($connection !== 'default') {
            $uses[] = Connection::class;
            $attributes[] = "#[Connection('$connection')]";
        }

        $primaryKeys = $metadata[Db::METADATA_PRIMARY_KEY];
        if ($primaryKey = count($primaryKeys) === 1 ? $primaryKeys[0] : null) {
            if ($primaryKey !== 'id' && $primaryKey !== $table . '_id' && $primaryKey !== $table . 'Id') {
                $uses[] = PrimaryKey::class;
                $attributes[] = "#[PrimaryKey('$primaryKey')]";
            }
        }

        if ($camelized) {
            $uses[] = ColumnMap::class;
            $attributes[] = "#[ColumnMap(ColumnMap::STRATEGY_SNAKE_CASE)]";
        }

        sort($uses);

        if ($uses !== []) {
            foreach ($uses as $use) {
                $str .= "use $use;" . PHP_EOL;
            }

            $str .= PHP_EOL;
        }

        foreach ($attributes as $attribute) {
            $str .= $attribute . PHP_EOL;
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

        if ($connection !== 'default') {
            $str .= PHP_EOL;
            $str .= '    public function connection(): string' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= "        return '$connection';" . PHP_EOL;
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

        $str .= '}';

        return $str;
    }

    /**
     * @param string $connection
     * @param string $table
     * @param string $rootNamespace
     *
     * @return string
     */
    protected function renderTable(string $connection, string $table, string $rootNamespace = 'App\Models'): string
    {
        $plainClass = Str::pascalize($table);
        $modelName = $rootNamespace . '\\' . $plainClass;

        if ($constants = $this->getConstantsByDb($connection, $table)) {
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

        if ($connection !== 'default') {
            $str .= PHP_EOL;
            $str .= '    public function connection()' . PHP_EOL;
            $str .= '    {' . PHP_EOL;
            $str .= "        return '$connection';" . PHP_EOL;
            $str .= '    }' . PHP_EOL;
        }

        $str .= '}';

        return $str;
    }

    /**
     * list databases and tables
     *
     * @param array  $connections   connections name list
     * @param string $table_pattern match table against a pattern
     *
     * @return void
     */
    public function listAction(array $connections = [], string $table_pattern = ''): void
    {
        foreach ($connections ?: $this->getConnections() as $connection) {
            $db = $this->dbFactory->get($connection);

            $this->console->writeLn("connection: `$connection`");
            foreach ($this->getTables($connection, $table_pattern) as $row => $table) {
                $columns = (array)$db->getMetadata($table)[Db::METADATA_ATTRIBUTES];
                $primaryKey = $db->getMetadata($table)[Db::METADATA_PRIMARY_KEY];
                foreach ($columns as $i => $column) {
                    if (in_array($column, $primaryKey, true)) {
                        $columns[$i] = $this->console->colorize($column, Console::FC_RED);
                    }
                }

                $colored_table = $this->console->colorize($table, Console::FC_GREEN);
                $this->console->writeLn(sprintf('%2d %s(%s)', $row + 1, $colored_table, implode(', ', $columns)));
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
            if (!in_array($area, ['Api', 'Admin', 'User'], true)) {
                $areas[] = $area;
            }
        }

        return $areas;
    }

    /**
     * generate model file in online
     *
     * @param string $table      table name
     * @param string $connection connection name
     * @param bool   $optimized  output as more methods as possible
     * @param bool   $camelized
     *
     * @return void
     */
    public function modelAction(string $table, string $connection = '', bool $optimized = false, bool $camelized = false
    ): void {
        if ($connection) {
            $db = $this->dbFactory->get($connection);
            if (!in_array($table, $db->getTables(), true)) {
                throw new Exception(['`:table` is not exists', 'table' => $table]);
            }
        } else {
            foreach ($this->getConnections() as $s) {
                $db = $this->dbFactory->get($s);
                if (in_array($table, $db->getTables(), true)) {
                    $connection = $s;
                    break;
                }
            }
            if (!$connection) {
                throw new Exception(['`:table` is not found in connections`', 'table' => $table]);
            }
        }

        $plainClass = Str::pascalize($table);
        $fileName = "@runtime/db_model/$plainClass.php";
        $class = "App\Models\\$plainClass";
        $model_str = $this->renderModel($connection, $class, $table, $optimized, $camelized);
        LocalFS::filePut($fileName, $model_str);

        $this->console->writeLn("`$table` table saved to `$fileName`");
    }

    /**
     * generate models file in online
     *
     * @param array  $connections   connections name list
     * @param string $table_pattern match table against a pattern
     * @param bool   $optimized     output as more methods as possible
     * @param bool   $camelized
     *
     * @return void
     */
    public function modelsAction(array $connections = [], string $table_pattern = '', bool $optimized = false,
        bool $camelized = false
    ): void {
        $areas = $this->getAreas();

        foreach ($connections ?: $this->getConnections() as $connection) {
            foreach ($this->getTables($connection, $table_pattern) as $table) {
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

                $model_str = $this->renderModel($connection, $class, $table, $optimized, $camelized);
                LocalFS::filePut($fileName, $model_str);

                $this->console->writeLn(" `$table` table saved to `$fileName`");
            }
        }
    }

    /**
     * generate models file in online
     *
     * @param array  $connections   connections name list
     * @param string $table_pattern match table against a pattern
     * @param string $namespace     namespace of models
     *
     * @return void
     */
    public function tablesAction(array $connections = [], string $table_pattern = '', string $namespace = 'App\Tables'
    ): void {
        if (!str_contains($namespace, '\\')) {
            $namespace = 'App\\' . ucfirst($namespace) . '\\Tables';
        }

        foreach ($connections ?: $this->getConnections() as $connection) {
            foreach ($this->getTables($connection, $table_pattern) as $table) {

                $plainClass = Str::pascalize($table);
                $fileName = "@runtime/db_tables/$plainClass.php";
                $model_str = $this->renderTable($connection, $table, $namespace);
                LocalFS::filePut($fileName, $model_str);

                $this->console->writeLn(" `$table` table saved to `$fileName`");
            }
        }
    }

    /**
     * export db data to csv files
     *
     * @param array  $connections   connections name list
     * @param string $table_pattern match table against a pattern
     *
     * @return void
     */
    public function jsonAction(array $connections = [], string $table_pattern = ''): void
    {
        foreach ($connections ?: $this->getConnections() as $connection) {
            $db = $this->dbFactory->get($connection);
            foreach ($this->getTables($connection, $table_pattern) as $table) {
                $fileName = "@runtime/db_json/$connection/$table.json";

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
                $this->console->writeLn(
                    sprintf('write to `%s` success: `%d` `[%.3f]`', $fileName, count($rows), $elapsed)
                );
            }
        }
    }

    /**
     * export db data to csv files
     *
     * @param array  $connections   connections name list
     * @param string $table_pattern match table against a pattern
     * @param bool   $bom           contains BOM or not
     *
     * @return void
     */
    public function csvAction(array $connections = [], string $table_pattern = '', bool $bom = false): void
    {
        foreach ($connections ?: $this->getConnections() as $connection) {
            $db = $this->dbFactory->get($connection);
            foreach ($this->getTables($connection, $table_pattern) as $table) {

                $fileName = "@runtime/db_csv/$connection/$table.csv";
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
                $this->console->writeLn(
                    sprintf(' `%s` imported to `%s`:%d [%.3f]', $table, $fileName, $count, $elapsed)
                );
            }
        }
    }
}