<?php
namespace ManaPHP\Model;

interface ValidatorInterface
{
    /**
     * @param \ManaPHP\Model $model
     * @param array          $fields
     *
     * @return  array
     */
    public function validate($model, $fields = []);
}