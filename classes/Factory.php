<?php

namespace Factory;

use Database\Database;

/**
 * @author  Каймонов Владимир
 * @version 1.0
 *
 * @property-read Database $db
 *
 */
class Factory
{
    /**
     * @var Database Содержит объект для работы с базой данных
     */
    private $_database;

    /**
     * Возвращает значение указанного свойства
     *
     * @param string $name Название свойства
     *
     * @return mixed
     */
    public function __get($name)
    {
        switch ($name) {
            case 'db':
                if ($this->_database === null) {
                    $this->_database = new Database();
                    $this->_database->connection($databaseName = 'doingsdone');
                }

                return $this->_database;
        }

        return null;
    }
}