<?php

namespace ManaPHP {

    /**
     * ManaPHP\Flash
     *
     * Shows HTML notifications related to different circumstances. Classes can be stylized using CSS
     *
     *<code>
     * $flash->success("The record was successfully deleted");
     * $flash->error("Cannot open the file");
     *</code>
     */
    abstract class Flash extends Component implements FlashInterface
    {
        protected $_cssClasses;

        /**
         * \ManaPHP\Flash constructor
         *
         * @param array $cssClasses
         */
        public function __construct($cssClasses = null)
        {
            $this->_cssClasses = $cssClasses ?: [
                'error' => 'flash_error_message',
                'notice' => 'flash_notice_message',
                'success' => 'flash_success_message',
                'warning' => 'flash_warning_message'
            ];
        }

        /**
         * Shows a HTML error message
         *
         *<code>
         * $flash->error('This is an error');
         *</code>
         *
         * @param string $message
         *
         * @return void
         */
        public function error($message)
        {
            $this->_message('error', $message);
        }

        /**
         * Shows a HTML notice/information message
         *
         *<code>
         * $flash->notice('This is an information');
         *</code>
         *
         * @param string $message
         *
         * @return void
         */
        public function notice($message)
        {
            $this->_message('notice', $message);
        }

        /**
         * Shows a HTML success message
         *
         *<code>
         * $flash->success('The process was finished successfully');
         *</code>
         *
         * @param string $message
         *
         * @return void
         */
        public function success($message)
        {
            $this->_message('notice', $message);
        }

        /**
         * Shows a HTML warning message
         *
         *<code>
         * $flash->warning('Hey, this is important');
         *</code>
         *
         * @param string $message
         *
         * @return void
         */
        public function warning($message)
        {
            $this->_message('warning', $message);
        }

        /**
         * @param string $type
         * @param string $message
         *
         * @return string
         */
        abstract protected function _message($type, $message);
    }
}
