<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;
use ManaPHP\Dotenv;

class EnvController extends Controller
{
    /**
     * @return array
     */
    protected function _getEnvFiles()
    {
        $files = [];

        foreach (glob($this->alias->resolve('@root/.env.*')) as $env) {
            $env = basename($env);
            if (!in_array($env, ['.env', '.env.php', '.env.dist', '.env.sample'], true)) {
                $files[] = substr($env, 5);
            }
        }
        return $files;
    }

    /**
     * switch .env
     *
     * @param string $target
     *
     * @return int
     */
    public function switchCommand($target = '')
    {
        if ($target === '' && $values = $this->arguments->getValues()) {
            $target = $values[0];
        }

        if ($target === '') {
            $target = $this->arguments->getOption('env');
        }

        $candidates = [];
        foreach ($this->_getEnvFiles() as $file) {
            if (strpos($file, $target) === 0) {
                $candidates[] = $file;
            }
        }

        if (count($candidates) !== 1) {
            return $this->console->error(['can not one file: :env', 'env' => implode(',', $candidates)]);
        }
        $target = $candidates[0];
        $file = '@root/.env.' . $target;
        if (!$this->filesystem->fileExists($file)) {
            return $this->console->error(['dotenv file `:file` is not exists', 'file' => $file]);
        } else {
            $this->filesystem->fileCopy($file, '@root/.env', true);
            $this->console->writeLn(['copy dotenv file `:src` to `:dst` success.', 'src' => $file, 'dst' => '@root/.env']);
            return 0;
        }
    }

    /**
     * list all env type
     */
    public function listCommand()
    {
        $this->console->writeLn('list: ' . implode(',', $this->_getEnvFiles()));
    }

    /**
     * parse .env file and save to .env.php
     */
    public function cacheCommand()
    {
        $file = $this->alias->resolve('@root/.env');
        if (!file_exists($file)) {
            return $this->console->writeLn(['`:file` dotenv file is not exists', 'file' => $file]);
        }

        $data = (new Dotenv())->parse(file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        $content = '<?php' . PHP_EOL .
            'return ' . var_export($data, true) . PHP_EOL;
        $this->filesystem->filePut('@root/.env.php', $content);

        return 0;
    }

    /**
     * show parsed .env file as json string
     */
    public function inspectCommand()
    {
        $file = $this->alias->resolve('@root/.env');
        if (!file_exists($file)) {
            return $this->console->writeLn(['`:file` dotenv file is not exists', 'file' => $file]);
        }

        $data = (new Dotenv())->parse(file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));

        $this->console->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return 0;
    }
}