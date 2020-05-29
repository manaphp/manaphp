<?php

namespace ManaPHP\Model;

interface SerializeNormalizable
{
    /**
     * @param array $data
     *
     * @return array
     */
    public function serializeNormalize($data);
}