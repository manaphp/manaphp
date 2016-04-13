<?php

namespace ManaPHP\Mvc\View {

    class Tag
    {
        /**
         * @var string
         */
        protected $_documentTitle = '';

        /**
         * @var string
         */
        protected $_documentDescription;

        /**
         * Set the title of view content
         *
         *<code>
         * $tag->setTitle('Welcome to my Page');
         *</code>
         *
         * @param string $title
         *
         * @return static
         */
        public function setTitle($title)
        {
            $this->_documentTitle = $title;

            return $this;
        }

        /**
         * @param $title
         *
         * @return static
         */
        public function appendTitle($title)
        {
            $this->_documentTitle .= $title;

            return $this;
        }

        /**
         * @return string
         */
        public function getTitle()
        {
            return $this->_documentTitle;
        }

        /**
         * @param string $description
         *
         * @return static
         */
        public function setDescription($description)
        {
            $this->_documentDescription = $description;

            return $this;
        }

        /**
         * @return string
         */
        public function getDescription()
        {
            return $this->_documentDescription;
        }

    }
}