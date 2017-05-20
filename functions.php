<?php

function include_template(string $path, array $data = [])
{
    if (!file_exists($path)) {
        return '';
    }

    $data = array_map(function ($value) {
        return xss_clean($value);
    }, $data);

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

/**
 * Производит подключение к указанной базе данных
 *
 * @param string $database_name Название базы данных
 *
 * @return mysqli
 */
function db_connection($database_name)
{
    $conn_id = mysqli_connect($host = '127.0.0.1', $user = 'root', $password = '', $database_name);
    if (!$conn_id) {
        echo 'Не удалось подключиться к базе данных!';
        exit;
    }

    return $conn_id;
}

/**
 * Выполняет указанный запрос
 *
 * @param mysqli $conn_id mysqli Ресурс соединения
 * @param string $sql     SQL запрос
 * @param array  $data    Данные для вставки на место плейсхолдеров
 *
 * @return array Возвращает выбранные данные
 */
function db_query($conn_id, string $sql, array $data = [])
{
    $stmt = db_get_prepare_stmt($conn_id, $sql, $data);
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
 * @param mysqli $conn_id mysqli Ресурс соединения
 * @param string $sql     SQL запрос
 * @param array  $data    Данные для вставки на место плейсхолдеров
 *
 * @return bool|int Возвращает идентификатор последней добавленной записи или false в случаи ошибки
 */
function db_insert($conn_id, string $sql, array $data)
{

    $stmt = db_get_prepare_stmt($conn_id, $sql, $data);
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
 * @param mysqli $conn_id    mysqli Ресурс соединения
 * @param string $table_name Название таблицы
 * @param array  $data       Данные для обновления
 * @param array  $where      Данные для формирования условия
 *
 * @return bool|int Возвращает количество изменённых записей или false в случаи ошибки
 */
function db_update($conn_id, string $table_name, array $data, array $where)
{

    $set_fields = create_placeholder_for_query(array_keys($data));
    $where_fields = create_placeholder_for_query(array_keys($where));

    $sql = "UPDATE `{$table_name}` SET {$set_fields} WHERE {$where_fields};";

    $stmt = db_get_prepare_stmt($conn_id, $sql, array_merge($data, $where));
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
 * Создаёт placeholder-ы для запроса
 *
 * @param array $data Данные
 *
 * @return string
 */
function create_placeholder_for_query(array $data)
{
    $data = array_map(function ($value) {
        return "`{$value}` = ?";
    }, $data);

    return implode(', ', $data);
}

/**
 * Создаёт placeholder-ы для запроса insert
 *
 * @param int $count Количество placeholder-ов
 *
 * @return string
 */
function create_placeholders_for_insert(int $count)
{
    return implode(', ', array_fill(0, $count, '?'));
}

/**
 * Формирует список полей для запроса insert
 *
 * @param array $fields_names Список названий полей
 *
 * @return string
 */
function create_fields_for_insert(array $fields_names)
{
    return implode(', ', array_reduce($fields_names, function ($carry, $field_name) {
        $carry[] = "`{$field_name}`";

        return $carry;
    }));
}