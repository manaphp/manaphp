<?php

namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;
use ManaPHP\Helper\LocalFS;

class EnvController extends Controller
{
    /**
     * @return array
     */
    protected function _getEnvTypes()
    {
        $files = [];

        foreach (glob($this->alias->resolve('@root/.env[._-]*')) as $env) {
            $env = basename($env);
            $ext = pathinfo($env, PATHINFO_EXTENSION);
            if ($ext === 'php') {
                $env = substr($env, 0, -4);
            }

            if (preg_match('#[.\-_](php|dist|example)$#', $env)) {
                continue;
            }

            $files[] = substr($env, 5);
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
        if ($target === '' && $values = $this->request->getValues()) {
            $target = $values[0];
        }

        if ($target === '') {
            $target = $this->request->get('env');
        }

        $candidates = [];
        foreach ($this->_getEnvTypes() as $file) {
            if (strpos($file, $target) === 0) {
                $candidates[] = $file;
            }
        }

        if (count($candidates) !== 1) {
            return $this->console->error(['can not one file: :env', 'env' => implode(',', $candidates)]);
        }
        $target = $candidates[0];

        $glob = '@root/.env[._-]' . $target;
        $files = LocalFS::glob($glob);
        if ($files) {
            $file = $files[0];
            LocalFS::fileCopy($file, '@root/.env', true);
            if (file_exists($file . '.php')) {
                LocalFS::fileDelete($file . '.php');
            }
            $this->console->writeLn(['copy `:src` to `:dst` success.', 'src' => basename($file), 'dst' => '.env']);
            return 0;
        } else {
            return $this->console->error(['dotenv file `:file` is not exists', 'file' => $glob]);
        }
    }

    /**
     * list all env type
     */
    public function listCommand()
    {
        $this->console->writeLn('list: ' . implode(' ', $this->_getEnvTypes()));
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

        $data = $this->_di->getShared('ManaPHP\Dotenv')->parse(file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        $content = '<?php' . PHP_EOL .
            'return ' . var_export($data, true) . ';' . PHP_EOL;
        LocalFS::filePut('@root/.env.php', $content);

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

        $data = $this->_di->getShared('ManaPHP\Dotenv')->parse(file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));

        $this->console->write(json_stringify($data, JSON_PRETTY_PRINT));

        return 0;
    }
}