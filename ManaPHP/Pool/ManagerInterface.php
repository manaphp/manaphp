<?php
namespace ManaPHP\Pool;

interface ManagerInterface
{
    /**
     * @param object $owner
     * @param object $sample
     * @param int    $size
     * @param string $type
     *
     * @return static
     */
    public function add($owner, $sample, $size = 1, $type = 'default');

    /**
     * @param object $owner
     * @param object $instance
     * @param string $type
     *
     * @return static
     */
    public function push($owner, $instance, $type = 'default');

    /**
     * @param object $owner
     * @param string $type
     * @param float  $timeout
     *
     * @return mixed
     *
     * @throws \ManaPHP\Pool\NotExistsException
     */
    public function pop($owner, $type = 'default', $timeout = null);

    /**
     * @param object $owner
     * @param string $type
     *
     * @return bool
     */
    public function exists($owner, $type = 'default');
}