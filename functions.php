<?php

function include_template(string $path, array $vars = [])
{
    if (!file_exists($path)) {
        return '';
    }

    foreach ($vars as $var_name => $var_value) {
        ${$var_name} = xss_clean($var_value);
    }

    ob_start();

    include $path;

    $buffer = ob_get_contents();

    ob_end_clean();

    return $buffer;
}

function xss_clean($value)
{
    if (!($value and (is_string($value) or is_array($value)))) {
        return $value;
    }

    if (is_array($value)) {
        return array_map(function ($value) {
            return xss_clean($value);
        }, $value);
    }

    $value = htmlspecialchars($value);

    return $value;
}
