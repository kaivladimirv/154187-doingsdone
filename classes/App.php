<?php

namespace App;

use Factory\Factory;

/**
 * Класс для управления web-приложением
 *
 * @author  Каймонов Владимир
 * @version 1.0
 *
 */
class App
{
    /**
     * @var \Factory\Factory
     */
    private static $_factory;

    /**
     * @var array Список ошибок произошедших при регистрации
     */
    private static $registerErrors = [];

    /**
     * @var array Список ошибок произошедших при аутентификации
     */
    private static $loginErrors = [];

    /**
     * @var array Содержит данные о текущем пользователе
     */
    private static $currentUser = [];

    /**
     * @var boolean Указывает требуется или нет открыть форму аутентификации
     */
    private static $openLoginForm = false;

    /**
     * @var boolean Указывает требуется или нет открыть форму регистрации
     */
    private static $openRegisterForm = false;

    /**
     * @var integer Содержит код текущего проекта
     */
    private static $currentProjectCode = null;

    /**
     * @var array Список ошибок произошедших при добавлении новой задачи
     */
    private static $errorsInNewTask = [];

    /**
     * @var boolean Указывает требуется или нет открыть форму добавление новой задачи
     */
    private static $openAddTaskForm = false;

    /**
     * @var boolean Указывает нужно или нет отобразить завершенные задачи
     */
    private static $isShowCompletedTasks = false;

    public function __construct()
    {
        self::$_factory = new Factory();
    }

    /**
     * Инициализирует web-приложение
     *
     */
    public static function init()
    {
        self::authentication();
        self::registration();

        self::detectWhetherOpenLoginForm();
        self::detectWhetherOpenRegisterForm();

        self::showCompletedTasks();

        self::detectCurrentProjectCode();
        self::isExistsCurrentProject();

        self::addNewTask();
        self::detectWhetherOpenAddTaskForm();
        self::completeTask();

        self::detectWhetherShowCompletedTasks();

        self::showPage();
    }

    /**
     * Отображает главную страницу
     *
     */
    private static function showPage()
    {
        echo self::$_factory->templ->includeTemplate('templates/index.php', self::getData());
    }

    /**
     * Возвращает данные необходимые для отображения страницы
     *
     * @return array
     */
    private static function getData()
    {
        return [
            'factory'              => self::$_factory,
            'registerErrors'       => self::$registerErrors,
            'loginErrors'          => self::$loginErrors,
            'currentUser'          => self::$currentUser,
            'openLoginForm'        => self::$openLoginForm,
            'openRegisterForm'     => self::$openRegisterForm,
            'currentProjectCode'   => self::$currentProjectCode,
            'errorsInNewTask'      => self::$errorsInNewTask,
            'openAddTaskForm'      => self::$openAddTaskForm,
            'isShowCompletedTasks' => self::$isShowCompletedTasks,
        ];
    }

    /**
     * Производит аутентификацию
     *
     */
    private static function authentication()
    {
        if (self::$_factory->auth->isAuthenticated()) {
            self::$currentUser = self::$_factory->auth->getCurrentUser();
        } elseif (self::$_factory->request->isRequestLogin()) {
            self::$loginErrors = self::$_factory->auth->authenticate();
            if (!is_array(self::$loginErrors)) {
                header('Location: /');
                exit;
            }
        }
    }

    /**
     * Производит регистрацию пользователь, если поступил такой запрос
     *
     */
    private static function registration()
    {
        if (!self::$currentUser and self::$_factory->request->isRequestRegister()) {
            self::$registerErrors = self::$_factory->auth->registration();
            if (!is_array(self::$registerErrors)) {
                header('Location: /index.php?login_form=&is_registered');
                exit;
            }
        }
    }

    /**
     * Определяет нужно или нет открыть форму аутентификации
     *
     */
    private static function detectWhetherOpenLoginForm()
    {
        self::$openLoginForm = (self::$_factory->request->isRequestForOpenLoginForm() or self::$loginErrors);
    }

    /**
     * Определяет нужно или нет открыть форму регистрации
     *
     */
    private static function detectWhetherOpenRegisterForm()
    {
        self::$openRegisterForm = (!self::$currentUser and (self::$_factory->request->isRequestForOpenRegisterForm() or self::$registerErrors));
    }

    /**
     * Отображает/скрывает завершенные задачи
     *
     */
    private static function showCompletedTasks()
    {
        if (self::$currentUser and self::$_factory->request->isRequestForShowCompletedTasks()) {
            self::setShowCompletedTasks();
            header('Location: /');
            exit;
        }
    }

    /**
     * Определяет код текущего проекта
     *
     */
    private static function detectCurrentProjectCode()
    {
        self::$currentProjectCode = self::getCurrentProjectCode();
    }

    /**
     * Возвращает код текущего проекта
     *
     * @return integer
     */
    private static function getCurrentProjectCode()
    {
        return (isset($_GET['project']) ? (int) $_GET['project'] : self::$_factory->projects::ALL_PROJECTS_CODE);
    }

    /**
     * Определяет существует ли текущий проект
     *
     */
    private static function isExistsCurrentProject()
    {
        if (self::$currentUser and !self::$_factory->projects->isExists(self::$currentUser['code'], self::$currentProjectCode)) {
            header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
            exit;
        }
    }

    /**
     * Производит добавление новой задачи, если поступил соответствующий запрос
     *
     */
    private static function addNewTask()
    {
        self::$errorsInNewTask = [];
        if (self::$_factory->request->isRequestForAddTask()) {
            self::$errorsInNewTask = self::$_factory->tasks->append(self::$currentUser['code']);
            if (!is_array(self::$errorsInNewTask)) {
                header('Location: /');
                exit;
            }
        }
    }

    /**
     * Определяет нужно или нет открыть форму добавления новой задачи
     *
     */
    private static function detectWhetherOpenAddTaskForm()
    {
        self::$openAddTaskForm = (self::$_factory->request->isRequestForOpenAddTaskForm() or self::$errorsInNewTask);
    }

    /**
     * Производит завершение задачи, если поступил соответствующий запрос
     *
     */
    private static function completeTask()
    {
        if (self::$currentUser and self::$_factory->request->isRequestForCompleteTask()) {
            self::$_factory->tasks->complete(self::getCompleteTaskCode());
        }
    }

    /**
     * Возвращает код задачи, которую нужно отметить как завершенную
     *
     * @return integer
     */
    private static function getCompleteTaskCode()
    {
        return (int) $_GET['complete_task'];
    }

    /**
     * Определяет нужно или нет отображать завершенные задачи
     *
     */
    private static function detectWhetherShowCompletedTasks()
    {
        self::$isShowCompletedTasks = (isset($_COOKIE['show_completed']) and ((int) $_COOKIE['show_completed'] === 1));
    }

    /**
     * Устанавливает куку указывающую нужно или нет отображать завершенные задачи
     *
     */
    private static function setShowCompletedTasks()
    {
        $showCompleted = intval($_GET['show_completed']);

        setcookie('show_completed', $showCompleted, strtotime('+30days'));
    }
}