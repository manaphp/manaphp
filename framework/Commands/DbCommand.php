<?php

declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Alias\Path;
use ManaPHP\Cli\Command;
use ManaPHP\Cli\Console;
use ManaPHP\Db\Db;
use ManaPHP\Db\DbFactoryInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Persistence\Attribute\Connection;
use function array_keys;
use function basename;
use function count;
use function dirname;
use function fclose;
use function file;
use function fnmatch;
use function fopen;
use function fprintf;
use function fputcsv;
use function fwrite;
use function implode;
use function in_array;
use function json_stringify;
use function microtime;
use function preg_match;
use function preg_split;
use function sort;
use function sprintf;
use function str_contains;
use function str_replace;
use function strpos;
use function strrpos;
use function substr;
use function trim;
use function ucfirst;

class DbCommand extends Command
{
    #[Autowired] protected DbFactoryInterface $dbFactory;

    protected array $tableConstants = [];

    /**
     * @param string $connection
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
     * @param string $entityName
     *
     * @return string
     */
    protected function getConstantsByFile(string $entityName): string
    {
        $file = "@app/Entities/$entityName.php";
        if (!LocalFS::fileExists($file)) {
            return '';
        }

        $constants = '';
        foreach (file(Path::resolve($file)) as $line) {
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

    protected function dbTypeToPhpType(string $type): string
    {
        if (preg_match('#^(TINYINT|SMALLINT|MEDIUMINT|INT|BIGINT)#i', $type)) {
            return 'int';
        } elseif (preg_match('#^(CHAR|VARCHAR|BINARY|VARBINARY|BLOB|TEXT|ENUM|SET)#i', $type)) {
            return 'string';
        } elseif (preg_match('#^(TINYBLOB|BLOB|MEDIUMBLOB|LONGBLOB)#i', $type)) {
            return 'string';
        } elseif (preg_match('#^(TINYTEXT|TEXT|MEDIUMTEXT|LONGTEXT)#i', $type)) {
            return 'string';
        } elseif (preg_match('#^(DECIMAL|NUMERIC|DOUBLE)#i', $type)) {
            return 'string';
        } elseif (preg_match('#^(DATE|TIME|DATETIME|TIMESTAMP)#i', $type)) {
            return 'string';
        } else {
            return 'mixed';
        }
    }

    /**
     * @param string $connection
     * @param string $class
     * @param string $table
     * @param bool $camelized
     *
     * @return string
     */
    protected function renderEntity(string $connection, string $class, string $table, bool $camelized = false): string
    {
        $db = $this->dbFactory->get($connection);
        $metadata = $db->getMetadata($table);

        $fields = (array)$metadata[Db::METADATA_ATTRIBUTES];

        $pos = strrpos($class, '\\');
        $plainClass = substr($class, $pos + 1);
        $namespace = substr($class, 0, $pos);

        if ($constants = $this->getConstantsByDb($connection, $table)) {
            SuppressWarnings::noop();
        } elseif ($constants = $this->getConstantsByFile($plainClass)) {
            $constants = '    ' . $constants;
        }

        $str = '<?php' . PHP_EOL;
        $str .= 'declare(strict_types=1);' . PHP_EOL . PHP_EOL;
        $str .= "namespace $namespace;" . PHP_EOL;
        $str .= PHP_EOL;

        $uses = [];
        if (strpos($class, '\\Areas\\')) {
            $uses[] = 'App\Entities\Entity';
            $uses[] = 'ManaPHP\Persistence\Attribute\Table';
        }

        $attributes = [];
        if ($connection !== 'default') {
            $uses[] = Connection::class;
            $attributes[] = "#[Connection('$connection')]";
        }

        if (($primaryKey = $metadata[Db::METADATA_PRIMARY_KEY][0] ?? '') !== '') {
            $uses[] = 'ManaPHP\Persistence\Attribute\Id';
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

        if (str_contains($class, '\\Areas\\')) {
            $str .= sprintf("#[Table('%s')]", $table) . PHP_EOL;
        }
        $str .= 'class ' . $plainClass . ' extends Entity' . PHP_EOL;
        $str .= '{';
        if ($constants) {
            $str .= PHP_EOL . $constants . PHP_EOL;
        }

        $str .= PHP_EOL;
        foreach ($fields as $field => $type) {
            if ($field === $primaryKey) {
                $str .= '    #[Id]' . PHP_EOL;
            }
            $field = $camelized ? Str::camelize($field) : $field;
            $php_type = $this->dbTypeToPhpType($type);
            $str .= "    public $php_type $$field;" . PHP_EOL;
        }

        $str .= '}';

        return $str;
    }

    /**
     * @param string $entity
     * @param string $repository
     *
     * @return string
     */
    protected function renderRepository(string $entity, string $repository): string
    {
        $pos = strrpos($entity, '\\');
        $plainEntity = substr($entity, $pos + 1);

        $pos = strrpos($repository, '\\');
        $plainRepository = substr($repository, $pos + 1);
        $namespace = substr($repository, 0, $pos);

        $str = '<?php' . PHP_EOL . PHP_EOL;
        $str .= 'declare(strict_types=1);' . PHP_EOL . PHP_EOL;
        $str .= "namespace $namespace;" . PHP_EOL;
        $str .= PHP_EOL;

        $str .= "use $entity;" . PHP_EOL;
        $str .= "use ManaPHP\\Db\\Repository;" . PHP_EOL . PHP_EOL;
        $str .= '/**' . PHP_EOL;
        $str .= " * @extends Repository<$plainEntity>" . PHP_EOL;
        $str .= ' */' . PHP_EOL;
        $str .= "class $plainRepository extends Repository" . PHP_EOL;
        $str .= '{' . PHP_EOL;
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
    protected function renderTable(string $connection, string $table, string $rootNamespace = 'App\Entities'): string
    {
        $plainClass = Str::pascalize($table);
        $entityClass = $rootNamespace . '\\' . $plainClass;

        if ($constants = $this->getConstantsByDb($connection, $table)) {
            SuppressWarnings::noop();
        } elseif ($constants = $this->getConstantsByFile($plainClass)) {
            $constants = '    ' . $constants;
        }

        $str = '<?php' . PHP_EOL . PHP_EOL;
        $str .= 'namespace ' . substr($entityClass, 0, strrpos($entityClass, '\\')) . ';' . PHP_EOL;
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
     * @param array $connections connections name list
     * @param string $table_pattern match table against a pattern
     *
     * @return void
     */
    public function listAction(array $connections = [], string $table_pattern = ''): void
    {
        foreach ($connections ?: $this->dbFactory->getNames() as $connection) {
            $db = $this->dbFactory->get($connection);

            $this->console->writeLn("connection: $connection");
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
     * generate entity file in online
     *
     * @param string $table table name
     * @param string $connection connection name
     * @param bool $camelized
     *
     * @return void
     */
    public function entityAction(string $table, string $connection = '', bool $camelized = false): void
    {
        if ($connection) {
            $db = $this->dbFactory->get($connection);
            if (!in_array($table, $db->getTables(), true)) {
                throw new Exception('Table "{table}" does not exist in the database.', ['table' => $table]);
            }
        } else {
            foreach ($this->dbFactory->getNames() as $s) {
                $db = $this->dbFactory->get($s);
                if (in_array($table, $db->getTables(), true)) {
                    $connection = $s;
                    break;
                }
            }
            if (!$connection) {
                throw new Exception('Table "{table}" is not found in connections.', ['table' => $table]);
            }
        }

        $plainClass = Str::pascalize($table);
        $fileName = "@runtime/db_entity/$plainClass.php";
        $class = "App\Entities\\$plainClass";
        $entity_str = $this->renderEntity($connection, $class, $table, $camelized);
        LocalFS::filePut($fileName, $entity_str);

        $this->console->writeLn("'$table' table saved to '$fileName'");
    }

    /**
     * generate entities file in online
     *
     * @param array $connections connections name list
     * @param string $table_pattern match table against a pattern
     * @param bool $optimized output as more methods as possible
     * @param bool $camelized
     *
     * @return void
     */
    public function entitiesAction(array $connections = [], string $table_pattern = '', bool $camelized = false): void
    {
        $areas = $this->getAreas();

        foreach ($connections ?: $this->dbFactory->getNames() as $connection) {
            foreach ($this->getTables($connection, $table_pattern) as $table) {
                $plainClass = Str::pascalize($table);
                $entity = "App\Entities\\$plainClass";
                if (($pos = strpos($table, '_')) !== false) {
                    $area = Str::pascalize(substr($table, 0, $pos));
                    if (in_array($area, $areas, true)) {
                        $entity = sprintf(
                            "App\\Areas\\%s\\Entities\\%s", Str::pascalize($area),
                            Str::pascalize(substr($table, $pos))
                        );
                    }
                }

                $entityFile = '@runtime/db_entities/' . str_replace('\\', '/', $entity) . '.php';
                $entityContent = $this->renderEntity($connection, $entity, $table, $camelized);
                LocalFS::filePut($entityFile, $entityContent);

                $repository = str_replace("\\Entities\\", "\\Repositories\\", $entity) . 'Repository';
                $repositoryFile = '@runtime/db_entities/' . str_replace('\\', '/', $repository) . '.php';
                $repositoryContent = $this->renderRepository($entity, $repository);
                LocalFS::filePut($repositoryFile, $repositoryContent);
                $this->console->writeLn(" '$table' table saved to '$entityFile'");
            }
        }
    }

    /**
     * generate table files in online
     *
     * @param array $connections connections name list
     * @param string $table_pattern match table against a pattern
     * @param string $namespace namespace of entities
     *
     * @return void
     */
    public function tablesAction(
        array  $connections = [],
        string $table_pattern = '',
        string $namespace = 'App\Tables'
    ): void
    {
        if (!str_contains($namespace, '\\')) {
            $namespace = 'App\\' . ucfirst($namespace) . '\\Tables';
        }

        foreach ($connections ?: $this->dbFactory->getNames() as $connection) {
            foreach ($this->getTables($connection, $table_pattern) as $table) {

                $plainClass = Str::pascalize($table);
                $fileName = "@runtime/db_tables/$plainClass.php";
                $entity_str = $this->renderTable($connection, $table, $namespace);
                LocalFS::filePut($fileName, $entity_str);

                $this->console->writeLn(" '$table' table saved to '$fileName'");
            }
        }
    }

    /**
     * export db data to csv files
     *
     * @param array $connections connections name list
     * @param string $table_pattern match table against a pattern
     *
     * @return void
     */
    public function jsonAction(array $connections = [], string $table_pattern = ''): void
    {
        foreach ($connections ?: $this->dbFactory->getNames() as $connection) {
            $db = $this->dbFactory->get($connection);
            foreach ($this->getTables($connection, $table_pattern) as $table) {
                $fileName = "@runtime/db_json/$connection/$table.json";

                LocalFS::dirCreate(dirname($fileName));
                $table = $db->getPrefix() . $table;
                $rows = $db->fetchAll("SELECT * FROM [$table]");
                $file = fopen(Path::resolve($fileName), 'wb');

                $startTime = microtime(true);
                foreach ($rows as $row) {
                    fwrite($file, json_stringify($row) . PHP_EOL);
                }
                fclose($file);

                $elapsed = microtime(true) - $startTime;
                $this->console->writeLn(
                    sprintf('write to "%s" success: "%d" "[%.3f]"', $fileName, count($rows), $elapsed)
                );
            }
        }
    }

    /**
     * export db data to csv files
     *
     * @param array $connections connections name list
     * @param string $table_pattern match table against a pattern
     * @param bool $bom contains BOM or not
     *
     * @return void
     */
    public function csvAction(array $connections = [], string $table_pattern = '', bool $bom = false): void
    {
        foreach ($connections ?: $this->dbFactory->getNames() as $connection) {
            $db = $this->dbFactory->get($connection);
            foreach ($this->getTables($connection, $table_pattern) as $table) {

                $fileName = "@runtime/db_csv/$connection/$table.csv";
                LocalFS::dirCreate(dirname($fileName));
                $table = $db->getPrefix() . $table;
                $rows = $db->fetchAll("SELECT * FROM [$table]");

                $file = fopen(Path::resolve($fileName), 'wb');

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
                    sprintf(' "%s" imported to "%s":%d [%.3f]', $table, $fileName, $count, $elapsed)
                );
            }
        }
    }
}
