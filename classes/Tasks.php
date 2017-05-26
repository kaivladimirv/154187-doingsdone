<?php

namespace Tasks;

/**
 * Класс для работы со списком задач
 *
 * @author  Каймонов Владимир
 * @version 1.0
 *
 */
class Tasks
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
     * Возвращает список задач пользователя по указанному проекту
     *
     * @param integer $userCode    Код пользователя
     * @param integer $projectCode Код проекта
     *
     * @return array
     */
    public function getListByProject(int $userCode, int $projectCode)
    {
        $wherePlaceholders = [];
        $whereData = [];
        $this->setWhereUserCode($wherePlaceholders, $whereData, $userCode);
        $this->setWhereProjectCode($wherePlaceholders, $whereData, $projectCode);

        return $this->getListByWhere($wherePlaceholders, $whereData);
    }

    /**
     * Возвращает список активных задач пользователя по указанному проекту
     *
     * @param integer $userCode    Код пользователя
     * @param integer $projectCode Код проекта
     *
     * @return array
     */
    public function getListOfActiveByProject(int $userCode, int $projectCode)
    {
        $wherePlaceholders = [];
        $whereData = [];
        $this->setWhereUserCode($wherePlaceholders, $whereData, $userCode);
        $this->setWhereProjectCode($wherePlaceholders, $whereData, $projectCode);
        $this->setWhereActive($wherePlaceholders, $whereData);

        return $this->getListByWhere($wherePlaceholders, $whereData);
    }

    /**
     * Возвращает количество задач по указанному проекту
     *
     * @param integer $userCode    Код пользователя
     * @param integer $projectCode Код проекта
     *
     * @return integer
     */
    public function getCountByProject(int $userCode, int $projectCode)
    {
        $wherePlaceholders = [];
        $whereData = [];
        $this->setWhereUserCode($wherePlaceholders, $whereData, $userCode);
        $this->setWhereProjectCode($wherePlaceholders, $whereData, $projectCode);

        return $this->getCountByWhere($wherePlaceholders, $whereData);
    }

    /**
     * Производит форматирование списка задача для отображения на странице
     *
     * @param array $tasks Список задач
     *
     * @return array
     */
    public function formatterForDisplay(array $tasks)
    {
        $currentTime = time();

        return array_map(function ($task) use ($currentTime) {
            $task['date_deadline'] = ($task['date_deadline'] ? date('d.m.Y', strtotime($task['date_deadline'])) : '');

            $task['class_names'] = [];

            if ($task['is_done']) {
                $task['class_names'][] = 'task--completed';
            } elseif ($task['date_deadline']) {
                $days_until_deadline = $this->getDaysBetween($currentTime, strtotime($task['date_deadline']));
                $task['class_names'][] = ($days_until_deadline <= 0 ? 'task--important' : '');
            }

            return $task;
        }, $tasks);
    }

    /**
     * Производит добавление новой задачи
     *
     * @param integer $userCode Код пользователя
     *
     * @return integer|array Возвращает код новой задачи или массив содержащий список ошибок
     */
    public function append(int $userCode)
    {
        if ($errors = $this->validationBeforeAppend()) {
            return $errors;
        }

        $newTask = [
            'name'          => $_POST['name'],
            'project_code'  => $_POST['project'],
            'creator_code'  => $userCode,
            'date_creation' => date('Y-m-d H:i:s', time()),
            'date_deadline' => date('Y-m-d H:i:s', strtotime($_POST['date'])),
        ];

        $taskCode = $this->appendToDb($newTask);
        if (!$taskCode) {
            return $errors = ['name' => 'Не удалось создать задачу'];
        }

        if (!$this->uploadFile($taskCode)) {
            return $errors = ['preview' => 'Произошла ошибка при загрузке файла'];
        }

        return $taskCode;
    }

    /**
     * Завершает указанную задачу
     *
     * @param integer $taskCode Код задачи
     *
     * @return boolean
     */
    public function complete(int $taskCode)
    {
        $data = [
            'is_done'         => 1,
            'date_completion' => date('Y-m-d H:i:s', time()),
        ];
        $where = ['code' => $taskCode];

        return ($this->_factory->db->update('tasks', $data, $where) !== false);
    }

    /**
     * Производит добавление новой задачи в базу данных
     *
     * @param array $newTask Данные
     *
     * @return integer|boolean Возвращает код новой задачи или false в случаи ошибки
     */
    private function appendToDb(array $newTask)
    {
        $fields = $this->_factory->db->createFieldsForInsert(array_keys($newTask));
        $placeholders = $this->_factory->db->createPlaceholdersForInsert(count($newTask));

        return $this->_factory->db->insert("INSERT INTO tasks ({$fields}) VALUES ({$placeholders});", $newTask);
    }

    /**
     * Производит загрузку файла и прикрепление его к указанной задаче
     *
     * @param integer $taskCode Код задачи, к которой нужно прикрепить загружаемый файл
     *
     * @return boolean
     */
    private function uploadFile(int $taskCode)
    {
        if (!$this->isAttachedFile()) {
            return true;
        }

        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/';
        $uploadFileName = $uploadDir . basename($_FILES['preview']['name']);

        $isMoved = move_uploaded_file($_FILES['preview']['tmp_name'], $uploadFileName);
        if (!$isMoved) {
            return false;
        }

        $data = ['path_to_file' => $uploadFileName];
        $where = ['code' => $taskCode];

        return ($this->_factory->db->update('tasks', $data, $where) !== false);
    }

    /**
     * Производит проверку данных перед добавлением новой задачи
     *
     * @return array Возвращает список ошибок или пустой массив, если ошибок не обнаружено
     */
    private function validationBeforeAppend()
    {
        $errors = [];

        if (!isset($_POST['name']) or !$_POST['name']) {
            $errors['name'] = 'Не указано название задачи';
        }
        if (!isset($_POST['project']) or !$_POST['project']) {
            $errors['project'] = 'Не указан проект';
        }
        if (!isset($_POST['date']) or !$_POST['date']) {
            $errors['date'] = 'Не указана дата выполнения';
        } else {
            $partsDate = explode('.', $_POST['date']);
            if ((count($partsDate) != 3) or !checkdate($partsDate[1], $partsDate[0], $partsDate[2])) {
                $errors['date'] = 'Не корректно указана дата выполнения';
            }
        }

        if ($this->isAttachedFile()) {
            if ($_FILES['preview']['error'] != UPLOAD_ERR_OK) {
                $errors['preview'] = 'Произошла ошибка при загрузке файла';
            } elseif (!is_uploaded_file($_FILES['preview']['tmp_name'])) {
                $errors['preview'] = 'Возможная атака с участием загрузки файла';
            }
        }

        return $errors;
    }

    private function isAttachedFile()
    {
        return (isset($_FILES['preview']) and $_FILES['preview']['name']);
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
        $wherePlaceholders[] = '(creator_code = ?)';
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
        if ($projectCode != $this->_factory->projects::ALL_PROJECTS_CODE) {
            $wherePlaceholders[] = '(project_code = ?)';
            $whereData[] = $projectCode;
        }
    }

    /**
     * Устанавливает условие для выборки активных задач
     *
     * @param array $wherePlaceholders Список условий с плейсхолдерами вместо значений
     * @param array $whereData         Данные для вставки на место плейсхолдеров
     *
     */
    private function setWhereActive(array &$wherePlaceholders, array &$whereData)
    {
        $wherePlaceholders[] = '(is_done = ?)';
        $whereData[] = 0;
    }

    /**
     * Возвращает список задача по указанному условию
     *
     * @param array $wherePlaceholders Список условий с плейсхолдерами вместо значений
     * @param array $whereData         Данные для вставки на место плейсхолдеров
     *
     * @return array
     */
    private function getListByWhere(array $wherePlaceholders = [], array $whereData = [])
    {
        $wherePlaceholders = ($wherePlaceholders ? 'WHERE ' . implode(' AND ', $wherePlaceholders) : '');

        $sql = "SELECT * FROM tasks {$wherePlaceholders};";

        return $this->_factory->db->query($sql, $whereData);
    }

    /**
     * Возвращает количество задач по указанному условию
     *
     * @param array $wherePlaceholders Список условий с плейсхолдерами вместо значений
     * @param array $whereData         Данные для вставки на место плейсхолдеров
     *
     * @return integer
     */
    private function getCountByWhere(array $wherePlaceholders = [], array $whereData = [])
    {
        $wherePlaceholders = ($wherePlaceholders ? 'WHERE ' . implode(' AND ', $wherePlaceholders) : '');

        $sql = "SELECT COUNT(*) AS tasks_count FROM tasks {$wherePlaceholders};";

        $data = $this->_factory->db->query($sql, $whereData);

        return ($data ? $data[0]['tasks_count'] : 0);
    }

    /**
     * Возвращает количество дней между двумя временными метками
     *
     * @param integer $start Начальная метка времени Unix
     * @param integer $end   Конечная метка времени Unix
     *
     * @return integer
     */
    private function getDaysBetween(int $start, int $end)
    {
        return ($this->convertTimestampToDays($end) - ($this->convertTimestampToDays($start) + 1));
    }

    /**
     * Преобразует метку времени Unix в дни
     *
     * @param integer $timestamp Метка времени Unix
     *
     * @return integer
     */
    private function convertTimestampToDays(int $timestamp)
    {
        $secondsInDay = 86400;

        return intval(floor($timestamp / $secondsInDay));
    }
}