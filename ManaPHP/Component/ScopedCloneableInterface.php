<?php

namespace ManaPHP\Component;

interface ScopedCloneableInterface
{
    /**
     * @param  \ManaPHP\Component $scope
     * @return static
     */
    public function getScopedClone($scope);
}