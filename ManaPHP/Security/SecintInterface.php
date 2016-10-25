<?php
namespace ManaPHP\Security;

/**
 * Interface ManaPHP\Security\SecintInterface
 *
 * @package secint
 */
interface SecintInterface
{
    /**
     * Encodes a variable number of parameters to generate a hash.
     *
     * @param int    $id
     * @param string $type
     *
     * @return string the generated hash
     */
    public function encode($id, $type = '');

    /**
     * Decodes a hash to the original parameter values.
     *
     * @param string $hash the hash to decode
     * @param string $type
     *
     * @return int|false
     */
    public function decode($hash, $type = '');
}