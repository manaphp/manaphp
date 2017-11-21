<?php
namespace ManaPHP\Mvc\View;

use ManaPHP\Component;

/**
 * Class ManaPHP\Mvc\View\Form
 *
 * @package ManaPHP\Mvc\View
 *
 * @property \ManaPHP\Http\RequestInterface  $request
 * @property \ManaPHP\Http\ResponseInterface $response
 * @property \ManaPHP\Http\FilterInterface   $filter
 */
class Form extends Component implements FormInterface
{
    /**
     * @var string
     */
    protected $_formName = '';

    /**
     * @var array
     */
    protected $_rules = [];

    /**
     * @var bool
     */
    protected $_isSubmitted;

    public function setDependencyInjector($dependencyInjector)
    {
        parent::setDependencyInjector($dependencyInjector);

        if ($this->isSubmitted()) {
            $this->loadForAction();
        } else {
            $this->loadForView();
        }

        return $this;
    }

    /**
     * @param string $field
     * @param string $value
     */
    public function validateField($field, $value)
    {
        if (isset($this->_rules[$field])) {
            $this->filter->sanitize($field, $this->_rules[$field], $value);
        }
    }

    /**
     * @return string
     */
    public function getFormName()
    {
        return $this->_formName;
    }

    /**
     * @param string $formName
     *
     * @return string
     */
    public function setFormName($formName)
    {
        $oldFormName = $this->_formName;
        $this->_formName = $formName;

        return $oldFormName;
    }

    /**
     * @return bool
     */
    protected function _IsSubmitted()
    {
        if ($this->request->isPost()) {
            return true;
        } elseif ($this->request->has('do')) {
            return true;
        } else {
            foreach ($this->getFields() as $field) {
                if ($this->request->has($field)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    final public function isSubmitted()
    {
        if ($this->_isSubmitted === null) {
            $this->_isSubmitted = $this->_IsSubmitted();
        }

        return $this->_isSubmitted;
    }

    /**
     * @param bool $submitted
     */
    public function setSubmitted($submitted)
    {
        $this->_isSubmitted = $submitted;
    }

    /**
     * @param array $data
     */
    public function loadForAction($data = null)
    {
        if ($data === null) {
            $data = $_REQUEST;
        }
        $formName = $this->getFormName();

        if (isset($data[$formName])) {
            $data = $data[$formName];
        }

        foreach ($this->getFields() as $field) {
            if (isset($data[$field])) {
                $this->validateField($field, $data[$field]);

                if (method_exists($this, 'set' . $field)) {
                    $this->{'set' . $field}($data[$field]);
                } else {
                    $this->{$field} = $data[$field];
                }
            }
        }
    }

    /**
     *
     */
    public function loadForView()
    {

    }

    /**
     * @return array
     */
    public function getFields()
    {
        $fields = [];

        foreach (get_object_vars($this) as $k => $_) {
            if ($_ !== null && !is_scalar($_) && !$_ instanceof self) {
                continue;
            }

            if ($k[0] !== '_') {
                $fields[] = $k;
            }
        }

        return $fields;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $fields = [];

        foreach ($this->getFields() as $field) {
            $fields[$field] = $this->{$field};
        }

        return $fields;
    }
}