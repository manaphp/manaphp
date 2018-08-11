<?php
namespace ManaPHP\View;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidArgumentException;

/**
 * Class Html
 * @package ManaPHP\View
 * @property \ManaPHP\Http\RequestInterface $request
 */
class Html extends Component
{
    /**
     * @param string $name
     * @param array  $data
     *
     * @return string
     */
    public function render($name, $data)
    {
        if (isset($data['value'])) {
            $current_value = $data['value'];
            unset($data['value']);
        } else {
            $current_value = $this->request->getInput($name, '');
        }

        $method = '_render_' . $name;
        if (method_exists($this, $method)) {
            return $this->$method($data, $current_value);
        } else {
            throw new InvalidArgumentException('sss');
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
        } elseif ($data['option']) {
            $options = $data['option'];
            unset($data['option']);
        } elseif (isset($data['values'])) {
            $options = $data['values'];
            unset($data['values']);
        } else {
            throw new InvalidArgumentException('');
        }

        $r = '<select';
        foreach (['id', 'name', 'class'] as $attr) {
            if (isset($data[$attr])) {
                $r .= " $attr=\"$data[$attr]\"";
                unset($data[$attr]);
            }
        }

        foreach ($data as $attr => $value) {
            $r .= " $attr=\"$data[$attr]\"";
        }

        $r .= '>' . PHP_EOL;

        foreach ((array)$options as $value => $label) {
            $r .= "  <option value=\"$value\"";
            /** @noinspection TypeUnsafeComparisonInspection */
            if (($value === '' && $current_value === '') || ($current_value !== '' && $value == $current_value)) {
                $r .= ' selected';
            }
            $r .= '>' . htmlspecialchars($label) . '</option>' . PHP_EOL;
        }
        $r .= '</select>';

        return $r;
    }
}