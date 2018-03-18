<?php
namespace ManaPHP\Model\Validator;

class Message implements \JsonSerializable
{
    /**
     * @var string
     */
    public $template;

    /**
     * @var \ManaPHP\Model
     */
    public $model;

    /**
     * @var string
     */
    public $field;

    /**
     * @var array
     */
    public $parameters;

    /**
     * Message constructor.
     *
     * @param string         $template
     * @param \ManaPHP\Model $model
     * @param string         $field
     * @param array          $parameters
     */
    public function __construct($template, $model, $field, $parameters = [])
    {
        $this->template = $template;
        $this->model = $model;
        $this->field = $field;
        $this->parameters = $parameters;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $tr = [':field' => $this->field, ':value' => $this->model->{$this->field}];

        foreach ((array)$this->parameters as $z => $parameter) {
            $tr[':parameters[' . $z . ']'] = $parameter;
        }

        return strtr($this->template, $tr);
    }

    /**
     * @return mixed|string
     */
    public function jsonSerialize()
    {
        return $this->__toString();
    }
}