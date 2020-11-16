<?php

namespace ManaPHP\Data\Model;

interface SerializeNormalizable
{
    /**
     * @param array $data
     *
     * @return array
     */
    public function serializeNormalize($data);
}