<?php

function includeTemplate(string $path, array $data = [])
{
    if (!file_exists($path)) {
        return '';
    }

    $data = array_map(function ($value) {
        return xssClean($value);
    }, $data);

    ob_start();

    include $path;

    $buffer = ob_get_contents();

    ob_end_clean();

    return $buffer;
}

function xssClean($value)
{
    if (!($value and (is_string($value) or is_array($value)))) {
        return $value;
    }

    if (is_array($value)) {
        return array_map(function ($value) {
            return xssClean($value);
        }, $value);
    }

    $value = htmlspecialchars($value);

    return $value;
}
