<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;
use ManaPHP\Mvc\Model\Metadata;
use ManaPHP\Utility\Text;

/**
 * Class ModelController
 *
 * @package ManaPHP\Cli\Controllers
 * @property \ManaPHP\RendererInterface $renderer
 * @property \ManaPHP\Cli\Application   $application
 */
class ModelController extends Controller
{
    public function defaultCommand()
    {
        $module = $this->_getCurrentModule();

        if ($module === '') {
            $modelDirectory = '@app/Models';
            $modelNamespace = $this->alias->resolve('@ns.app\Models');
        } else {
            $modelDirectory = "@app/$module/Models";
            $modelNamespace = $this->alias->resolve("@ns.app\\$module\\Models");
        }
        $modelExtends = $this->filesystem->fileExists("$modelDirectory/ModelBase.php") ? 'ModelBase' : '\ManaPHP\Mvc\Model';

        $force = $this->arguments->has('force:f');

        $templateFile = '@manaphp/Cli/Controllers/Templates/Model';
        $tables = $this->arguments->get('table:t');
        $tables = $tables ? [$tables] : $this->_getTables();
        foreach ($tables as $table) {
            $modelName = Text::camelize($table);
            $modelFile = $this->alias->resolve($modelDirectory . '/' . $modelName . '.php');

            if ($this->filesystem->fileExists($modelFile)) {
                if ($force) {
                    $this->console->writeLn('`:model` has been overwrite', ['model' => $modelNamespace . '\\' . $modelName]);
                } else {
                    $this->console->writeLn('`:model` is already exists', ['model' => $modelNamespace . '\\' . $modelName]);
                    continue;
                }
            }

            $vars = [];

            $vars['columns'] = $this->db->getMetadata($table)[Metadata::MODEL_ATTRIBUTES];

            $vars['model_name'] = $modelName;
            $vars['model_namespace'] = $modelNamespace;
            $vars['model_extends'] = $modelExtends;
            $vars['model_file'] = $modelFile;
            $vars['table'] = $table;

            $this->filesystem->filePut($modelFile, $this->renderer->render($templateFile, $vars));
        }
    }

    /**
     * @return array
     */
    protected function _getTables()
    {
        $tables = [];
        foreach ($this->db->fetchAll('SHOW TABLES', [], \PDO::FETCH_BOTH) as $row) {
            $tables[] = $row[0];
        }

        return $tables;
    }

    /**
     * @param string $table
     *
     * @return array
     */
    protected function _getColumns($table)
    {
        $columns = [];

        foreach ($this->db->fetchAll("DESC `$table`") as $row) {
            $columns[$row['Field']] = 'string';
        }

        return $columns;
    }

    /**
     * @return false|string
     * @throws \ManaPHP\Cli\Controllers\Exception
     */
    protected function _getCurrentModule()
    {
        $module = $this->arguments->get('module:m');
        if (!$module) {
            return '';
        }

        $module = $this->crossword->guess($this->application->getModules(), $module);
        if (!$module) {
            throw new Exception('invalid module');
        }

        return $module;
    }
}