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
        if (strpos($file, $this->alias->get('@app')) === 0) {
            $_compiledFile = $this->alias->resolve('@data/sword' . str_replace($this->alias->get('@app'), '', $file));
        } elseif (strpos($file, $this->alias->get('@manaphp')) === 0) {
            $_compiledFile = $this->alias->resolve('@data/sword/manaphp/' . str_replace($this->alias->get('@manaphp'), '', $file));
        } else {
            $_compiledFile = $this->alias->resolve('@data/sword/mixed/' . md5($file));
        }

        if ($this->configure->debug || !file_exists($_compiledFile) || filemtime($file) > filemtime($_compiledFile)) {
            $dir = dirname($_compiledFile);

            /** @noinspection NotOptimalIfConditionsInspection */
            if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new SwordException(['create `:dir` directory failed: :last_error_message', 'dir' => $dir]);
            }
            if (file_put_contents($_compiledFile, $this->_dependencyInjector->getShared('swordCompiler')->compileString(file_get_contents($file)), LOCK_EX) === false) {
                throw new SwordException(['write compiled sword `:file` failed: :last_error_message', 'file' => $_compiledFile]);
            }
        }

        extract($vars, EXTR_SKIP);

        /** @noinspection PhpIncludeInspection */
        require $_compiledFile;
    }
}