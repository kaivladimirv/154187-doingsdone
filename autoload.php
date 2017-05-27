<?php

spl_autoload_register(function ($className) {
    $parsedClassName = explode('\\', $className);
    $className = (isset($parsedClassName[1]) ? $parsedClassName[1] : $className);

    require_once 'classes/' . $className . '.php';

});
