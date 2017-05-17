<?php

namespace ManaPHP;

/**
 * Class ManaPHP\Version
 *
 * @package version
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
        return '0.8.4';
    }
}
