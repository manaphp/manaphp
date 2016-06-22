<?php

namespace ManaPHP {

    use ManaPHP\Flash\AdapterInterface;

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
    abstract class Flash extends Component implements FlashInterface, AdapterInterface
    {
        protected $_cssClasses;

        /**
         * \ManaPHP\Flash constructor
         *
         * @param array $cssClasses
         */
        public function __construct($cssClasses = null)
        {
            parent::__construct();

            $this->_cssClasses = $cssClasses ?: [
                'error' => 'flash-error-message',
                'notice' => 'flash-notice-message',
                'success' => 'flash-success-message',
                'warning' => 'flash-warning-message'
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
         * Prints the messages in the session flasher
         *
         * @param $remove bool
         */
        public function output($remove = true)
        {
            return $this->_output($remove);
        }
    }
}
