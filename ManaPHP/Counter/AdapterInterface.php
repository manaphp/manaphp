<?php
namespace ManaPHP\Counter;

interface AdapterInterface
{
    /**
     * @param string $type
     * @param string $id
     *
     * @return int
     */
    public function _get($type, $id);

    /**
     * @param string $type
     * @param string $id
     * @param int    $step
     *
     * @return int
     */
    public function _increment($type, $id, $step = 1);

    /**
     * @param string $type
     * @param string $id
     *
     * @return void
     */
    public function _delete($type, $id);
}