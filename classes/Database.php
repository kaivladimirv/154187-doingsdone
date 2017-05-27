<?php

namespace Database;

/**
 * Класс для работы с базой данных
 *
 * @author  Каймонов Владимир
 * @version 1.0
 *
 */
class Database
{
    /**
     * @var string Адрес хоста
     */
    private $host;

    /**
     * @var string Название базы данных
     */
    private $databaseName;

    /**
     * @var string Имя пользователя для подключения к хосту
     */
    private $user;

    /**
     * @var string Пароль для подключения к хосту
     */
    private $password;

    /**
     * @var mysqli Объект, представляющий подключение к серверу MySQL
     */
    private $connId = null;

    public function __construct()
    {
        $this->readConnectionParams();
    }

    /**
     * Производит подключение к указанной базе данных
     *
     * @return boolean
     */
    public function connection()
    {
        $this->connId = mysqli_connect($this->host, $this->user, $this->password, $this->databaseName);

        return $this->isConnected();
    }

    /**
     * Выполняет указанный запрос
     *
     * @param string $sql  SQL запрос
     * @param array  $data Данные для вставки на место плейсхолдеров
     *
     * @return array Возвращает выбранные данные
     */
    public function query(string $sql, array $data = [])
    {
        $stmt = $this->getPrepareStmt($sql, $data);
        if (!$stmt) {
            return [];
        }

        if (!mysqli_stmt_execute($stmt)) {
            return [];
        }

        $result = mysqli_stmt_get_result($stmt);
        if (!$result) {
            return [];
        }

        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }

        mysqli_free_result($result);
        mysqli_stmt_close($stmt);

        return $data;
    }

    /**
     * Выполняет запрос на добавление данных в БД
     *
     * @param string $sql  SQL запрос
     * @param array  $data Данные для вставки на место плейсхолдеров
     *
     * @return int|boolean Возвращает идентификатор последней добавленной записи или false в случаи ошибки
     */
    public function insert(string $sql, array $data)
    {
        $stmt = $this->getPrepareStmt($sql, $data);
        if (!$stmt) {
            return false;
        }

        if (!mysqli_stmt_execute($stmt)) {
            return false;
        }

        $insert_id = mysqli_stmt_insert_id($stmt);

        mysqli_stmt_close($stmt);

        return $insert_id;
    }

    /**
     * Выполняет запрос на обновление данных в БД
     *
     * @param string $tableName Название таблицы
     * @param array  $data      Данные для обновления
     * @param array  $where     Данные для формирования условия
     *
     * @return bool|int Возвращает количество изменённых записей или false в случаи ошибки
     */
    public function update(string $tableName, array $data, array $where)
    {
        $setFields = $this->createPlaceholderForQuery(array_keys($data));
        $whereFields = $this->createPlaceholderForQuery(array_keys($where));

        $sql = "UPDATE `{$tableName}` SET {$setFields} WHERE {$whereFields};";

        $stmt = $this->getPrepareStmt($sql, array_merge($data, $where));
        if (!$stmt) {
            return false;
        }

        if (!mysqli_stmt_execute($stmt)) {
            return false;
        }

        $affected_rows = mysqli_stmt_affected_rows($stmt);

        mysqli_stmt_close($stmt);

        return $affected_rows;
    }

    /**
     * Возвращает текст последней ошибки
     *
     * @return string
     */
    public function getError()
    {
        return mysqli_error($this->connId);
    }

    /**
     * Создаёт плейсхолдеры для запроса insert
     *
     * @param integer $count Количество плейсхолдеров
     *
     * @return string
     */
    public function createPlaceholdersForInsert(int $count)
    {
        return implode(', ', array_fill(0, $count, '?'));
    }

    /**
     * Формирует список полей для запроса insert
     *
     * @param array $fieldsNames Список названий полей
     *
     * @return string
     */
    public function createFieldsForInsert(array $fieldsNames)
    {
        return implode(', ', array_reduce($fieldsNames, function ($carry, $fieldName) {
            $carry[] = "`{$fieldName}`";

            return $carry;
        }));
    }

    /**
     * Создаёт плейсхолдеры для запроса
     *
     * @param array $data Данные
     *
     * @return string
     */
    private function createPlaceholderForQuery(array $data)
    {
        $data = array_map(function ($value) {
            return "`{$value}` = ?";
        }, $data);

        return implode(', ', $data);
    }

    /**
     * Создает подготовленное выражение на основе готового SQL запроса и переданных данных
     *
     * @param string $sql  SQL запрос с плейсхолдерами вместо значений
     * @param array  $data Данные для вставки на место плейсхолдеров
     *
     * @return mysqli_stmt|boolean Подготовленное выражение
     */
    private function getPrepareStmt(string $sql, array $data = [])
    {
        if (!$this->isConnected()) {
            return false;
        }

        $stmt = mysqli_prepare($this->connId, $sql);
        if (!$stmt) {
            return false;
        }

        if ($data) {
            $types = '';
            $stmt_data = [];

            foreach ($data as $value) {
                $type = null;

                if (is_int($value)) {
                    $type = 'd';
                } else {
                    if (is_string($value)) {
                        $type = 's';
                    } else {
                        if (is_double($value)) {
                            $type = 'd';
                        }
                    }
                }

                if ($type) {
                    $types .= $type;
                    $stmt_data[] = $value;
                }
            }

            $values = array_merge([
                                      $stmt,
                                      $types,
                                  ], $stmt_data);

            $func = 'mysqli_stmt_bind_param';
            $func(...$values);
        }

        return $stmt;
    }

    /**
     * Проверяет установлено ли соединение с базой данных
     *
     * @return boolean
     */
    private function isConnected()
    {
        return ($this->connId !== false);
    }

    /**
     * Производит чтение параметров подключения к серверу базы данных
     *
     */
    private function readConnectionParams()
    {
        $json = file_get_contents('db_connection.json');
        $params = json_decode($json, true);
        if (!$params) {
            return;
        }

        $this->host = $params['host'];
        $this->databaseName = $params['database_name'];
        $this->user = $params['user'];
        $this->password = $params['password'];
    }
}