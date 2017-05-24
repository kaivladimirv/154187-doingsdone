<?php

namespace Projects;

use Tasks\Tasks;

/**
 * Класс для работы со список проектов
 *
 * @author  Каймонов Владимир
 * @version 1.0
 *
 */
class Projects
{
    /**
     * @var integer Содержит код обозначающий все проекты
     */
    const ALL_PROJECTS_CODE = 0;

    /**
     * @var \Factory\Factory
     */
    private $_factory;

    /**
     * @var Tasks Содержит объект для работы со списком задач
     */
    private $tasks;

    /**
     * @param \Factory\Factory $factory
     */
    public function __construct($factory)
    {
        $this->_factory = $factory;
        $this->tasks = new Tasks($factory);
    }

    /**
     * Возвращает список проектов по указанному пользователю
     *
     * @param integer $userCode Код пользователя
     *
     * @return array
     */
    public function getListByUser(int $userCode)
    {
        $wherePlaceholders = [];
        $whereData = [];
        $this->setWhereUserCode($wherePlaceholders, $whereData, $userCode);

        return $this->getListByWhere($wherePlaceholders, $whereData);
    }

    /**
     * Проверят существует ли указанный проект
     *
     * @param integer $userCode    Код пользователя
     * @param integer $projectCode Код проекта
     *
     * @return boolean
     */
    public function isExists(int $userCode, int $projectCode)
    {
        if ($projectCode == self::ALL_PROJECTS_CODE) {
            return true;
        }

        $wherePlaceholders = [];
        $whereData = [];
        $this->setWhereUserCode($wherePlaceholders, $whereData, $userCode);
        $this->setWhereProjectCode($wherePlaceholders, $whereData, $projectCode);

        $projects = $this->getListByWhere($wherePlaceholders, $whereData);

        return (is_array($projects) and $projects);
    }

    /**
     * Производит форматирование списка проектов для отображения на странице
     *
     * @param integer $userCode Код пользователя
     * @param array   $projects Список проектов
     *
     * @return array Возвращает отформатированный список проектов
     */
    public function formatterForDisplay(int $userCode, array $projects)
    {
        array_unshift($projects, [
            'code' => self::ALL_PROJECTS_CODE,
            'name' => 'Все',
        ]);

        return array_map(function ($project) use ($userCode) {
            $project['tasks_count'] = $this->tasks->getCountByProject($userCode, $project['code']);

            return $project;
        }, $projects);
    }

    /**
     * Устанавливает условие выборки по коду пользователя
     *
     * @param array   $wherePlaceholders Список условий с плейсхолдерами вместо значений
     * @param array   $whereData         Данные для вставки на место плейсхолдеров
     * @param integer $userCode          Код пользователя
     *
     */
    private function setWhereUserCode(array &$wherePlaceholders, array &$whereData, int $userCode)
    {
        $wherePlaceholders[] = '(tasks.creator_code = ?)';
        $whereData[] = $userCode;
    }

    /**
     * Устанавливает условие выборки по коду проекта
     *
     * @param array   $wherePlaceholders Список условий с плейсхолдерами вместо значений
     * @param array   $whereData         Данные для вставки на место плейсхолдеров
     * @param integer $projectCode       Код проекта
     *
     */
    private function setWhereProjectCode(array &$wherePlaceholders, array &$whereData, int $projectCode)
    {
        $wherePlaceholders[] = '(projects.code = ?)';
        $whereData[] = $projectCode;
    }

    /**
     * Возвращает список проектов по указанному условию
     *
     * @param array $wherePlaceholders Список условий с плейсхолдерами вместо значений
     * @param array $whereData         Данные для вставки на место плейсхолдеров
     *
     * @return array
     */
    private function getListByWhere(array $wherePlaceholders = [], array $whereData = [])
    {
        $wherePlaceholders = ($wherePlaceholders ? 'WHERE ' . implode(' AND ', $wherePlaceholders) : '');

        $sql = "
          SELECT DISTINCT projects.* 
          FROM projects
          JOIN tasks on projects.code = tasks.project_code
          {$wherePlaceholders};";

        return $this->_factory->db->query($sql, $whereData);
    }
}