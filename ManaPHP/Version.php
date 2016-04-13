<?php

namespace ManaPHP {

    /**
     * ManaPHP\Version
     *
     * This class allows to get the installed version of the framework
     */
    class Version
    {

        /**
         * Returns the active version (string)
         *
         * <code>
         * echo \ManaPHP\Version::get();
         * </code>
         *
         * @return string
         */
        public static function get()
        {
            return '0.5.0';
        }
    }
}
