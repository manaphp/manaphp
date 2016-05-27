<?php
namespace ManaPHP {

    interface DebuggerInterface
    {
        /**
         * @param bool $listenException
         *
         * @return static
         */
        public function start($listenException = true);

        /**
         * @param \Exception $exception
         *
         * @return bool
         */
        public function onUncaughtException(\Exception $exception);

        /**
         * @param mixed  $value
         * @param string $name
         *
         * @return static
         */
        public function dump($value, $name = null);

        /**
         * @param null|string $template
         *
         * @return string|array
         */
        public function output($template = 'Default');

        /**
         * @param string $template
         *
         * @return string
         */
        public function save($template='Default');
    }
}