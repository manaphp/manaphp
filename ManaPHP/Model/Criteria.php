<?php
namespace ManaPHP\Model;

use ManaPHP\Component;
use ManaPHP\Model\Criteria\Exception as CriteriaException;

abstract class Criteria extends Component implements CriteriaInterface
{
    /**
     * @var bool
     */
    protected $_multiple;

    /**
     * @param bool $multiple
     *
     * @return static
     */
    public function setFetchType($multiple)
    {
        $this->_multiple = $multiple;

        return $this;
    }

    /**
     * @return \ManaPHP\Model[]|\ManaPHP\Model|false
     * @throws \ManaPHP\Model\Criteria\Exception
     */
    public function fetch()
    {
        if ($this->_multiple === true) {
            return $this->fetchAll();
        } elseif ($this->_multiple === false) {
            return $this->fetchOne();
        } else {
            throw new CriteriaException('xxx');
        }
    }
}