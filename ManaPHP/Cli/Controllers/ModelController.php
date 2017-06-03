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
    /**
     * @CliCommand create model
     * @CliParam   --force,-f   force create models
     * @CliParam   --module,-m  the module name
     * @CliParam   --pattern,-p filter the tables with fnmatch
     * @CliParam   --table,-t   which table to create model
     */
    public function createCommand()
    {
        $module = $this->arguments->get('module:m');
        if ($module) {
            $module = $this->crossword->guess($this->application->getModules(), $module);
            if (!$module) {
                return $this->console->error('invalid `:module` module', ['module' => $this->arguments->get('module:m')]);
            }
        }

        if ($module === '') {
            $modelDirectory = '@app/Models';
            $modelNamespace = $this->alias->resolveNS('@ns.app\Models');
        } else {
            $modelDirectory = "@app/$module/Models";
            $modelNamespace = $this->alias->resolveNS("@ns.app\\$module\\Models");
        }
        $modelExtends = $this->filesystem->fileExists("$modelDirectory/ModelBase.php") ? 'ModelBase' : '\ManaPHP\Mvc\Model';

        $force = $this->arguments->has('force:f');

        $templateFile = '@manaphp/Cli/Controllers/Templates/Model';
        $tables = $this->arguments->get('table:t');
        $tables = $tables ? explode(',', $tables) : $this->db->getTables();
        $pattern = $this->arguments->get('pattern:p');

        foreach ($tables as $table) {
            if ($pattern && !fnmatch($pattern, $table)) {
                continue;
            }

            $modelName = Text::camelize($table);
            $modelFile = $this->alias->resolve($modelDirectory . '/' . $modelName . '.php');

            if ($this->filesystem->fileExists($modelFile)) {
                if ($force) {
                    $this->console->writeLn('`:model` has been overwrite', ['model' => $modelNamespace . '\\' . $modelName]);
                } else {
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
     * @CliCommand list tables
     * @CliParam   --pattern,-p  filter tables with fnmatch
     *
     * @return void
     */
    public function tablesCommand()
    {
        $tables = $this->db->getTables();
        sort($tables);

        $pattern = $this->arguments->get('pattern:p');
        $line = 0;
        foreach ($tables as $table) {
            if ($pattern && !fnmatch($pattern, $table)) {
                continue;
            }

            $this->console->writeLn(sprintf('%-3d ', $line++) . $table);
        }
    }
}