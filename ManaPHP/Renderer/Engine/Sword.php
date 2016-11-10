<?php
namespace ManaPHP\Renderer\Engine;

use ManaPHP\Component;
use ManaPHP\Renderer\Engine\Sword\Exception as SwordException;
use ManaPHP\Renderer\EngineInterface;

/**
 * Class ManaPHP\Renderer\Engine\Sword
 *
 * @package renderer\engine
 */
class Sword extends Component implements EngineInterface
{
    /**
     * @param string $file
     * @param array  $vars
     *
     * @return void
     * @throws \ManaPHP\Renderer\Engine\Sword\Exception
     */
    public function render($file, $vars = [])
    {
        $_compiledFile = $this->alias->resolve('@data/sword' . str_replace($this->alias->get('@app'), '', $file));

        if (!file_exists($_compiledFile) || filemtime($file) > filemtime($_compiledFile)) {
            $dir = dirname($_compiledFile);

            /** @noinspection NotOptimalIfConditionsInspection */
            if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new SwordException('create `:dir` directory failed: :message', ['dir' => $dir, 'message' => SwordException::getLastErrorMessage()]);
            }
            if (file_put_contents($_compiledFile, $this->_dependencyInjector->getShared('swordCompiler')->compileString(file_get_contents($file)), LOCK_EX) === false) {
                throw new SwordException('write compiled sword `:file` failed: :message', ['file' => $_compiledFile, 'message' => SwordException::getLastErrorMessage()]);
            }
        }

        extract($vars, EXTR_SKIP);

        /** @noinspection PhpIncludeInspection */
        require $_compiledFile;
    }
}