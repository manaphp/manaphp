<?php

namespace ManaPHP {

    /**
     * ManaPHP\Loader
     *
     * This component helps to load your project classes automatically based on some conventions
     *
     *<code>
     * //Creates the autoloader
     * $loader = new ManaPHP\Loader();
     *
     * //Register some namespaces
     * $loader->registerNamespaces(array(
     *   'Example\Base' => 'vendor/example/base/',
     *   'Example\Adapter' => 'vendor/example/adapter/',
     *   'Example' => 'vendor/example/'
     * ));
     *
     * //register autoloader
     * $loader->register();
     *
     * //Requiring this class will automatically include file vendor/example/adapter/Some.php
     * $adapter = Example\Adapter\Some();
     *</code>
     */
    class Loader
    {
        /**
         * @var array
         */
        protected $_classes = [];

        /**
         * @var array
         */
        protected $_namespaces = [];

        /**
         * @var array
         */
        protected $_directories = [];

        /**
         * @var boolean
         */
        protected $_registered = false;

        public function __construct()
        {
            $this->register();
        }

        /**
         * Register namespaces and their related directories
         *
         * <code>
         * $loader->registerNamespaces(array(
         *        ’Example\\Base’ => ’vendor/example/base/’,
         *        ’Example\\Adapter’ => ’vendor/example/adapter/’,
         *        ’Example’ => ’vendor/example/’
         *        ));
         * </code>
         *
         * @param array   $namespaces
         * @param boolean $merge
         *
         * @return static
         */
        public function registerNamespaces($namespaces, $merge = false)
        {
            foreach ($namespaces as &$path) {
                $path = rtrim($path, '\\/');
                if (DIRECTORY_SEPARATOR === '\\') {
                    /** @noinspection ReferenceMismatchInspection */
                    $path = str_replace('\\', '/', $path);
                }
            }
            unset($path);

            $this->_namespaces = $merge ? array_merge($this->_namespaces, $namespaces) : $namespaces;

            return $this;
        }

        /**
         * Return current namespaces registered in the autoloader
         *
         * @return array
         */
        public function getNamespaces()
        {
            return $this->_namespaces;
        }

        /**
         * Register directories on which "not found" classes could be found
         *
         * <code>
         * $loader->registerDirs(
         *            array(
         *                __DIR__ . ’/models/’,
         *                ));
         * </code>
         *
         * @param array   $directories
         * @param boolean $merge
         *
         * @return static
         */
        public function registerDirs($directories, $merge = false)
        {
            foreach ($directories as &$directory) {
                $directory = rtrim($directory, '\\/');
                if (DIRECTORY_SEPARATOR === '\\') {
                    /** @noinspection ReferenceMismatchInspection */
                    $directory = str_replace('\\', '/', $directory);
                }
            }
            unset($directory);

            $this->_directories = $merge ? array_merge($this->_directories, $directories) : $directories;

            return $this;
        }

        /**
         * Return current directories registered in the autoloader
         *
         * @return array
         */
        public function getDirs()
        {
            return $this->_directories;
        }

        /**
         * Register classes and their locations
         *
         * @param array   $classes
         * @param boolean $merge
         *
         * @return static
         */
        public function registerClasses($classes, $merge = false)
        {
            if (DIRECTORY_SEPARATOR === '\\') {
                foreach ($classes as &$path) {
                    /** @noinspection ReferenceMismatchInspection */
                    $path = str_replace('\\', '/', $path);
                }
                unset($path);
            }

            $this->_classes = $merge ? array_merge($this->_classes, $classes) : $classes;

            return $this;
        }

        /**
         * Return the current class-map registered in the autoloader
         *
         * @return array
         */
        public function getClasses()
        {
            return $this->_classes;
        }

        /**
         * Register the autoload method
         *
         * @return static
         */
        public function register()
        {
            if (!$this->_registered) {
                spl_autoload_register([$this, '___autoload']);
                $this->_registered = true;
            }

            return $this;
        }

        /**
         * Unregister the autoload method
         *
         * @return static
         */
        public function unregister()
        {
            if ($this->_registered) {
                spl_autoload_unregister([$this, '___autoload']);
                $this->_registered = false;
            }

            return $this;
        }

        /**
         * If a file exists, require it from the file system.
         *
         * @param string $file The file to require.
         */
        protected function ___requireFile($file)
        {
            if (is_file($file)) {
                if (DIRECTORY_SEPARATOR === '\\') {
                    $realPath = str_replace('\\', '/', realpath($file));
                    if ($realPath !== $file) {
                        trigger_error("File name ($realPath) case mismatch for .$file", E_USER_ERROR);
                    }
                }
                /** @noinspection PhpIncludeInspection */
                require $file;
            }
        }

        /**
         * Makes the work of autoload registered classes
         *
         * @param string $className
         *
         * @return bool
         */
        protected function ___autoload($className)
        {
            if (is_array($this->_classes) && isset($this->_classes[$className])) {
                $this->___requireFile($this->_classes[$className]);

                return true;
            }

            if (is_array($this->_namespaces)) {
                /** @noinspection LoopWhichDoesNotLoopInspection */
                foreach ($this->_namespaces as $namespace => $directory) {
                    if (strpos($className, $namespace) !== 0) {
                        continue;
                    }
                    $len = strlen($namespace);
                    $file = $directory . str_replace('\\', '/', substr($className, $len)) . '.php';
                    $this->___requireFile($file);

                    return true;
                }
            }

            if (is_array($this->_directories)) {
                foreach ($this->_directories as $directory) {
                    $file = $directory . basename($className) . '.php';
                    $file = str_replace('\\', '/', $file);
                    if (file_exists($file)) {
                        $this->___requireFile($file);

                        return true;
                    }
                }
            }

            return false;
        }
    }
}
