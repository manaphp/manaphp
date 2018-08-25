<?php

use ManaPHP\Di;

if (!function_exists('widget')) {
    /**
     * @param string $name
     * @param array  $vars
     *
     * @return string|array
     */
    function widget($name, $vars = [])
    {
        return Di::getDefault()->view->widget($name, $vars);
    }
}

if (!function_exists('partial')) {
    /**
     * @param string $path
     * @param array  $vars
     *
     * @return void
     */
    function partial($path, $vars = [])
    {
        Di::getDefault()->view->partial($path, $vars);
    }
}

if (!function_exists('block')) {
    /**
     * @param string $path
     * @param array  $vars
     *
     * @return void
     */
    function block($path, $vars = [])
    {
        Di::getDefault()->view->block($path, $vars);
    }
}

if (!function_exists('pager')) {
    /**
     * @param \ManaPHP\Paginator|string $template
     * @param \ManaPHP\Paginator|string $pagination
     *
     * @return string
     */
    function pager($template = null, $pagination = null)
    {
        /** @noinspection SuspiciousAssignmentsInspection */
        $template = $pagination = null;

        foreach (func_get_args() as $value) {
            if (is_string($value)) {
                $template = $value;
            } elseif (is_object($value)) {
                $pagination = $value;
            }
        }

        if (!$pagination) {
            $pagination = di('paginator');
        }

        return $pagination->renderAsHtml($template);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * @return string
     */
    function csrf_token()
    {
        return di('csrfToken')->get();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * @return string
     */
    function csrf_field()
    {
        $csrfToken = di('csrfToken');
        return sprintf('<input type="hidden" name="%s" value="%s" />', $csrfToken->getName(), $csrfToken->get());
    }
}

if (!function_exists('bundle')) {
    /**
     * @param array  $files
     * @param string $name
     *
     * @return string
     */
    function bundle($files, $name = 'app')
    {
        return di('assetBundle')->bundle($files, $name);
    }
}
