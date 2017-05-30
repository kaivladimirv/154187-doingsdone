<?php

namespace Authentication;

/**
 * Класс для управления аутентификацией пользователя
 *
 * @author  Каймонов Владимир
 * @version 1.0
 *
 */
class Authentication
{
    /**
     * @var \Factory\Factory
     */
    private $_factory;

    /**
     * @var array Содержит данные о текущем пользователе
     */
    private $currentUser = [];

    /**
     * @param \Factory\Factory $factory
     */
    public function __construct($factory)
    {
        $this->_factory = $factory;
    }

    /**
     * Проверяет аутентифицирован ли текущий пользователь
     *
     * @return boolean
     */
    public function isAuthenticated()
    {
        session_start();

        if (!isset($_SESSION['email']) or !isset($_SESSION['password'])) {
            session_write_close();

            return false;
        }

        $this->currentUser = $this->_factory->users->getDataByEmail($_SESSION['email']);
        if ($this->currentUser and ($this->currentUser['password'] === $_SESSION['password'])) {
            return true;
        }
        $this->currentUser = [];

        session_destroy();

        return false;
    }

    /**
     * Выполнять аутентификацию пользователя
     *
     * @return boolean|array Возвращает true - в случаи успеха или массив содержащий ошибки
     */
    public function authenticate()
    {
        if ($errors = $this->validationLogin()) {
            return $errors;
        }

        return $this->createSession();
    }

    /**
     * Выполнять регистрацию нового пользователя
     *
     * @return boolean|array Возвращает true - в случаи успеха или массив содержащий ошибки
     */
    public function registration()
    {
        return $this->_factory->users->append();
    }

    /**
     * Разлогинивает текущего пользователя
     *
     */
    public function logout()
    {
        $this->currentUser = [];

        session_start();
        session_destroy();
    }

    /**
     * Возвращает данные о текущем пользователе
     *
     * @return array
     */
    public function getCurrentUser()
    {
        return $this->currentUser;
    }

    /**
     * Производит проверку данных пользователя необходимых для входа аутентификации
     *
     * @return array Возвращает информацию об ошибках. Если ошибок нет, то вернёт пустой массив.
     */
    private function validationLogin()
    {
        $errors = [];

        if (!isset($_POST['email']) or !$_POST['email']) {
            $errors['email'] = 'Не указан email';
        } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Некорректно указан email';
        }

        if (!isset($_POST['password']) or !$_POST['password']) {
            $errors['password'] = 'Не указан пароль';
        }
        if ($errors) {
            return $errors;
        }

        $user = $this->_factory->users->getDataByEmail($_POST['email']);
        if (!$user) {
            $errors['email'] = 'Пользователя с указанным email-ом не существует';
        } elseif (!password_verify($_POST['password'], $user['password'])) {
            $errors['password'] = 'Вы ввели неверный пароль';
        }

        return $errors;
    }

    /**
     * Создаёт новую сессию для текущего пользователя
     *
     * @return boolean
     */
    private function createSession()
    {
        $this->currentUser = $this->_factory->users->getDataByEmail($_POST['email']);

        session_start();

        $_SESSION['email'] = $this->currentUser['email'];
        $_SESSION['password'] = $this->currentUser['password'];

        return true;
    }
}