<?php

namespace Request;

/**
 * Класс для обработки HTTP-запросов
 *
 * @author  Каймонов Владимир
 * @version 1.0
 *
 */
class Request
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
     * Поступил запрос на аутентификацию
     *
     * @return boolean
     */
    public function isRequestLogin()
    {
        return isset($_GET['login']);
    }

    /**
     * Поступил запрос на открытие формы аутентификации
     *
     * @return boolean
     */
    public function isRequestForOpenLoginForm()
    {
        return isset($_GET['login_form']);
    }

    /**
     * Поступил запрос на регистрацию нового пользователя
     *
     * @return boolean
     */
    public function isRequestRegister()
    {
        return isset($_GET['register']);
    }

    /**
     * Поступил запрос на открытие формы регистрации нового пользователя
     *
     * @return boolean
     */
    public function isRequestForOpenRegisterForm()
    {
        return isset($_GET['register_form']);
    }

    /**
     * Поступил запрос на открытие формы добавления новой задачи
     *
     * @return boolean
     */
    public function isRequestForOpenAddTaskForm()
    {
        return isset($_GET['add']);
    }

    /**
     * Поступил запрос на добавление новой задачи
     *
     * @return boolean
     */
    public function isRequestForAddTask()
    {
        return isset($_POST['project']);
    }

    /**
     * Поступил запрос на отображение завершённых задач
     *
     * @return boolean
     */
    public function isRequestForShowCompletedTasks()
    {
        return isset($_GET['show_completed']);
    }

    /**
     * Поступил запрос на завершение задачи
     *
     * @return boolean
     */
    public function isRequestForCompleteTask()
    {
        return (isset($_GET['complete_task']) and is_numeric($_GET['complete_task']));
    }
}