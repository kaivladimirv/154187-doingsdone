<?php

function include_template(string $path, array $vars = [])
{
    if (!file_exists($path)) {
        return '';
    }

    $vars = xss_clean($vars);

    extract($vars);

    ob_start();

    require $path;

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

function get_tasks_count_by_project_name(array $tasks, string $project_name)
{
    if ($project_name == 'Все') {
        return count($tasks);
    }

    $count = 0;
    foreach ($tasks as $task) {
        if ($task['project_name'] == $project_name) {
            $count++;
        }
    }

    return $count;
}