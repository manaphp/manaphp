<?php
namespace ManaPHP\Renderer;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\MissingFieldException;

/**
 * Class Html
 * @package ManaPHP\Renderer
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class Html extends Component
{
    /**
     * @var string
     */
    protected $_select_all_text = '不限';

    /**
     * Html constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['select_all_text'])) {
            $this->_select_all_text = $options['select_all_text'];
        }
    }

    /**
     * @param string $name
     * @param array  $data
     *
     * @return string
     */
    public function render($name, $data)
    {
        if (isset($data['value'])) {
            $value = $data['value'];
            unset($data['value']);
        } elseif (isset($data['name'])) {
            $value = $this->request->getInput($data['name'], '');
        } else {
            $value = null;
        }

        $method = '_render_' . $name;
        if (method_exists($this, $method)) {
            return $this->$method($data, $value);
        } else {
            throw new InvalidArgumentException(['`:type` type is not exist', 'type' => $name]);
        }
    }

    /**
     * @param array $data
     * @param mixed $current_value
     *
     * @return string
     */
    protected function _render_select($data, $current_value)
    {
        if (isset($data['options'])) {
            $options = $data['options'];
            unset($data['options']);
        } elseif (isset($data['values'])) {
            $options = $data['values'];
            unset($data['values']);
        } else {
            throw new MissingFieldException('values');
        }

        if (isset($data['all'])) {
            if ($data['all']) {
                $options = array_merge(['' => $data['all']], $options);
            }
            unset($data['all']);
        } else {
            $options = array_merge(['' => $this->_select_all_text], $options);
        }

        $r = PHP_EOL . '  <select';
        foreach (['id', 'name', 'class'] as $attr) {
            if (isset($data[$attr])) {
                $r .= " $attr=\"$data[$attr]\"";
                unset($data[$attr]);
            }
        }

        foreach ($data as $attr => $value) {
            $r .= " $attr=\"" . htmlspecialchars($data[$attr]) . '"';
        }

        $r .= '>' . PHP_EOL;

        foreach ((array)$options as $value => $label) {
            $r .= '    <option value="' . (is_numeric($value) ? $value : htmlspecialchars($value)) . '"';
            /** @noinspection TypeUnsafeComparisonInspection */
	    
            $selected = false;
            if ($value === '') {
                if ($current_value === null || $current_value === '') {
                    $selected = true;
                }
            } elseif ($value === '0' || $value === 0) {
                if ($current_value === '0' || $current_value === 0) {
                    $selected = true;
                }
            } elseif ((string)$value === (string)$current_value) {
                $selected = true;
            }

            if ($selected) {
                $r .= ' selected';
            }
            $r .= '>' . htmlspecialchars($label) . '</option>' . PHP_EOL;
        }
        $r .= '  </select>' . PHP_EOL;

        return $r;
    }

    /**
     * @param array  $data
     * @param string $current_value
     *
     * @return string
     * @throws \ManaPHP\Exception\MissingFieldException
     */
    protected function _render_radio($data, $current_value)
    {
        if (isset($data['name'])) {
            $name = $data['name'];
        } else {
            throw new MissingFieldException('name');
        }

        if (isset($data['values'])) {
            $values = $data['values'];
        } else {
            throw new MissingFieldException('values');
        }

        $r = PHP_EOL;
        /** @noinspection ForeachSourceInspection */
        foreach ($values as $value => $label) {
            /** @noinspection TypeUnsafeComparisonInspection */
            $checked = ($current_value === '' && $value === '') || ($current_value !== '' && $value == $current_value);
            $r .= '  <label><input type="radio"'
                . " name=\"$name\" value=\"$value\" "
                . ($checked ? 'checked="checked"' : '')
                . '/><span>' . htmlspecialchars($label) . '</span></label>' . PHP_EOL;

        }

        return $r;
    }

    /**
     * @param array $data
     * @param array $current_value
     *
     * @return string
     * @throws \ManaPHP\Exception\MissingFieldException
     */
    protected function _render_checkbox($data, $current_value)
    {
        if (isset($data['name'])) {
            $name = $data['name'];
        } else {
            throw new MissingFieldException('name');
        }

        if (isset($data['values'])) {
            $values = $data['values'];
        } else {
            throw new MissingFieldException('values');
        }

        $r = PHP_EOL;
        /** @noinspection ForeachSourceInspection */
        foreach ($values as $value => $label) {
            $checked = is_array($current_value) && in_array($value, $current_value, false);
            $r .= '  <label><input type="checkbox"'
                . " name=\"{$name}[]\"  value=\"$value\" "
                . ($checked ? 'checked="checked"' : '')
                . '/><span>' . htmlspecialchars($label) . '</span></label>' . PHP_EOL;
        }

        return $r;
    }
}