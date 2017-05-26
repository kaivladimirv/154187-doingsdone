<?php

namespace Factory;

use Database\Database;
use Authentication\Authentication;
use Users\Users;
use Projects\Projects;
use Tasks\Tasks;
use Request\Request;
use TemplateEngine\TemplateEngine;

/**
 * @author  Каймонов Владимир
 * @version 1.0
 *
 * @property-read Database       $db
 * @property-read Authentication $auth
 * @property-read Users          $users
 * @property-read Projects       $projects
 * @property-read Tasks          $tasks
 * @property-read Request        $request
 * @property-read TemplateEngine $templ
 */
class Factory
{
    /**
     * @var Database Содержит объект для работы с базой данных
     */
    private $_database;

    /**
     * @var Authentication Содержит объект для управления аутентификацией пользователя
     */
    private $_auth;

    /**
     * @var Users Содержит объект для работы со списком пользователей
     */
    private $_users;

    /**
     * @var Projects Содержит объект для работы со списком проектов
     */
    private $_projects;

    /**
     * @var Tasks Содержит объект для работы со списком задач
     */
    private $_tasks;

    /**
     * @var Request Содержит объект для обработки HTTP-запросов
     */
    private $_request;

    /**
     * @var TemplateEngine Содержит объект для работы с шаблонами
     */
    private $_templ;

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
                return $this->initDb();

            case 'auth':
                return $this->initAuth();

            case 'users':
                return $this->initUsers();

            case 'projects':
                return $this->initProjects();

            case 'tasks':
                return $this->initTasks();

            case 'request':
                return $this->initRequest();

            case 'templ':
                return $this->initTemplateEngine();

        }

        return null;
    }

    /**
     * Инициализирует и возвращает объект для работы с базой данных
     *
     * @return mixed
     */
    private function initDb()
    {
        if ($this->_database === null) {
            $this->_database = new Database();
            $this->_database->connection();
        }

        return $this->_database;
    }

    /**
     * Инициализирует и возвращает объект для управления аутентификацией пользователя
     *
     * @return mixed
     */
    private function initAuth()
    {
        if ($this->_auth === null) {
            $this->_auth = new Authentication($this);
        }

        return $this->_auth;
    }

    /**
     * Инициализирует и возвращает объект для работы со списком пользователей
     *
     * @return mixed
     */
    private function initUsers()
    {
        if ($this->_users === null) {
            $this->_users = new Users($this);
        }

        return $this->_users;
    }

    /**
     * Инициализирует и возвращает объект для работы со списком проектов
     *
     * @return mixed
     */
    private function initProjects()
    {
        if ($this->_projects === null) {
            $this->_projects = new Projects($this);
        }

        return $this->_projects;
    }

    /**
     * Инициализирует и возвращает объект для работы со списком задач
     *
     * @return mixed
     */
    private function initTasks()
    {
        if ($this->_tasks === null) {
            $this->_tasks = new Tasks($this);
        }

        return $this->_tasks;
    }

    /**
     * Инициализирует и возвращает объект для обработки HTTP-запросов
     *
     * @return mixed
     */
    private function initRequest()
    {
        if ($this->_request === null) {
            $this->_request = new Request($this);
        }

        return $this->_request;
    }

    /**
     * Инициализирует и возвращает объект для работы с шаблонами
     *
     * @return mixed
     */
    private function initTemplateEngine()
    {
        if ($this->_templ === null) {
            $this->_templ = new TemplateEngine($this);
        }

        return $this->_templ;
    }

}