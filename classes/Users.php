<?php

namespace Users;

/**
 * Класс для работы со списком пользователей
 *
 * @author  Каймонов Владимир
 * @version 1.0
 *
 */
class Users
{
    /**
     * @var /Factory
     */
    private $_factory;

    /**
     * @param /Factory $factory
     */
    public function __construct($factory)
    {
        $this->_factory = $factory;
    }

    /**
     * Возвращает данные о пользователе по его электронному адресу
     *
     * @param string $email Электронный адрес пользователя
     *
     * @return array
     */
    public function getDataByEmail(string $email)
    {
        $wherePlaceholder[] = '(email = ?)';
        $whereData[] = $email;

        $data = $this->getListByWhere($wherePlaceholder, $whereData);

        return ($data ? $data[0] : []);
    }

    /**
     * Производит регистрацию нового пользователя
     *
     * @return boolean|array Возвращает true в случаи успеха или массив содержащий список ошибок
     */
    public function registration()
    {
        if ($errors = $this->validationAppend()) {
            return $errors;
        }

        $newUser = [
            'name'              => $_POST['name'],
            'email'             => $_POST['email'],
            'password'          => password_hash($_POST['password'], PASSWORD_DEFAULT),
            'date_registration' => date('Y-m-d H:i:s', time()),
        ];

        $userCode = $this->appendToDb($newUser);
        if (!$userCode) {
            return $errors = ['name' => 'Не удалось произвести регистрацию'];
        }

        return true;
    }

    /**
     * Производит проверку данных перед добавлением нового пользователя
     *
     * @return array Возвращает список ошибок или пустой массив, если ошибок не обнаружено
     */
    private function validationAppend()
    {
        $errors = [];

        if (!isset($_POST['email']) or !$_POST['email']) {
            $errors['email'] = 'Не указан email';
        }
        if (!isset($_POST['password']) or !$_POST['password']) {
            $errors['password'] = 'Не указан пароль';
        }
        if (!isset($_POST['name']) or !$_POST['name']) {
            $errors['name'] = 'Не указано имя';
        }
        if ($errors) {
            return $errors;
        }

        $user = $this->getDataByEmail($_POST['email']);
        if ($user) {
            $errors['email'] = 'Пользователь с указанным email-ом уже существует';
        }

        return $errors;
    }

    /**
     * Производит добавление нового пользователя в базу данных
     *
     * @param array $newUser Данные
     *
     * @return integer|boolean Возвращает код нового пользователя или false в случаи ошибки
     */
    private function appendToDb(array $newUser)
    {

        $fields = $this->_factory->db->createFieldsForInsert(array_keys($newUser));
        $placeholders = $this->_factory->db->createPlaceholdersForInsert(count($newUser));

        return $this->_factory->db->insert("INSERT INTO users ({$fields}) VALUES ({$placeholders});", $newUser);
    }

    /**
     * Возвращает список пользователей по указанному условию
     *
     * @param array $wherePlaceholders Список условий с плейсхолдерами вместо значений
     * @param array $whereData         Данные для вставки на место плейсхолдеров
     *
     * @return array
     */
    private function getListByWhere(array $wherePlaceholders = [], array $whereData = [])
    {
        $wherePlaceholders = ($wherePlaceholders ? 'WHERE ' . implode(' AND ', $wherePlaceholders) : '');

        return $this->_factory->db->query("SELECT * FROM users {$wherePlaceholders};", $whereData);
    }
}