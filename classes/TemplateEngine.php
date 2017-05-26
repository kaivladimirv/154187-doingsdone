<?php

namespace TemplateEngine;

/**
 * Класс для работы с шаблонами
 *
 * @author  Каймонов Владимир
 * @version 1.0
 *
 */
class TemplateEngine
{

    /**
     * @var \Factory\Factory
     */
    private $_factory;

    /**
     * @param \Factory\Factory $factory
     */
    public function __construct($factory)
    {
        $this->_factory = $factory;
    }

    /**
     * Подключает указанный шаблон
     *
     * @param string $path Путь к шаблону
     * @param array  $data Данные, которые будут использоваться в шаблоне
     *
     * @return string
     */
    public function includeTemplate(string $path, array $data = [])
    {
        if (!file_exists($path)) {
            return '';
        }

        $data = array_map(function ($value) {
            return $this->xssClean($value);
        }, $data);

        ob_start();

        include $path;

        $buffer = ob_get_contents();

        ob_end_clean();

        return $buffer;
    }

    /**
     * Производит фильтрацию данных
     *
     * @param mixed $value Значение для фильтрации
     *
     * @return string
     */
    private function xssClean($value)
    {
        if (!($value and (is_string($value) or is_array($value)))) {
            return $value;
        }

        if (is_array($value)) {
            return array_map(function ($value) {
                return $this->xssClean($value);
            }, $value);
        }

        $value = htmlspecialchars($value);

        return $value;
    }
}