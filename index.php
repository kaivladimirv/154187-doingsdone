<?php
require_once 'functions.php';

define('P_ALL', 0);
define('P_INCOME', 1);
define('P_LEARN', 2);
define('P_WORK', 3);
define('P_HOME', 4);
define('P_AUTO', 5);

$current_ts = time(); // текущая метка времени

$projects = [
    P_ALL    => 'Все',
    P_INCOME => 'Входящие',
    P_LEARN  => 'Учеба',
    P_WORK   => 'Работа',
    P_HOME   => 'Домашние дела',
    P_AUTO   => 'Авто',
];
$tasks = [
    [
        'name'          => 'Собеседование в IT компании',
        'date_deadline' => '01.06.2017',
        'project_code'  => P_WORK,
        'is_done'       => false,
    ],
    [
        'name'          => 'Выполнить тестовое задание',
        'date_deadline' => '25.05.2017',
        'project_code'  => P_WORK,
        'is_done'       => false,
    ],
    [
        'name'          => 'Сделать задание первого раздела',
        'date_deadline' => '21.04.2017',
        'project_code'  => P_LEARN,
        'is_done'       => true,
    ],
    [
        'name'          => 'Встреча с другом',
        'date_deadline' => '22.04.2017',
        'project_code'  => P_INCOME,
        'is_done'       => false,
    ],
    [
        'name'          => 'Купить корм для кота',
        'date_deadline' => '',
        'project_code'  => P_HOME,
        'is_done'       => false,
    ],
    [
        'name'          => 'Заказать пиццу',
        'date_deadline' => '',
        'project_code'  => P_HOME,
        'project_name'  => 'Домашние дела',
        'is_done'       => false,
    ],
];

$current_project_code = get_current_project_code();
if (!get_project_name_by_project_code($projects, $current_project_code)) {
    header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
    exit;
}

$get_tasks_count_by_project_code = function (array $tasks, int $project_code) {
    if ($project_code == P_ALL) {
        return count($tasks);
    }

    $count = 0;
    foreach ($tasks as $task) {
        if ($task['project_code'] == $project_code) {
            $count++;
        }
    }

    return $count;
};

$get_tasks_by_project_code = function (array $tasks, int $project_code) {
    if ($project_code == P_ALL) {
        return $tasks;
    }

    return array_filter($tasks, function ($task) use ($project_code) {
        return ($task['project_code'] == $project_code);
    });
};

$errors_in_new_task = [];
if (is_request_for_add_task()) {
    $errors_in_new_task = validation_task();
    if (!$errors_in_new_task) {
        $tasks = add_task($tasks);
        if (!upload_file()) {
            $errors_in_new_task['preview'] = 'Произошла ошибка при загрузке файла';
        }
    }
}

$open_add_task_form = (is_request_for_open_add_task_form() or $errors_in_new_task);

function get_current_project_code()
{
    return (isset($_GET['project']) ? (int) $_GET['project'] : P_ALL);
}

function get_project_name_by_project_code(array $projects, $project_code)
{
    return (isset($projects[$project_code]) ? $projects[$project_code] : null);
}

function is_request_for_open_add_task_form()
{
    return isset($_GET['add']);
}

function is_request_for_add_task()
{
    return isset($_POST['project']);
}

function add_task(array $tasks)
{
    array_unshift($tasks, [
        'name'          => $_POST['name'],
        'date_deadline' => $_POST['date'],
        'project_code'  => $_POST['project'],
        'is_done'       => false,
    ]);

    return $tasks;
}

function upload_file()
{
    if (!is_attached_file()) {
        return true;
    }

    $upload_dir = __DIR__ . '/';
    $upload_file = $upload_dir . basename($_FILES['preview']['name']);

    return move_uploaded_file($_FILES['preview']['tmp_name'], $upload_file);
}

function validation_task()
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

    if (is_attached_file()) {
        if ($_FILES['preview']['error'] != UPLOAD_ERR_OK) {
            $errors['preview'] = 'Произошла ошибка при загрузке файла';
        } elseif (!is_uploaded_file($_FILES['preview']['tmp_name'])) {
            $errors['preview'] = 'Возможная атака с участием загрузки файла';
        }
    }

    return $errors;
}

function is_attached_file()
{
    return (isset($_FILES['preview']) and $_FILES['preview']['name']);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Дела в Порядке!</title>
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="<?= ($open_add_task_form ? 'overlay' : ''); ?>">
<!--class="overlay"-->
<h1 class="visually-hidden">Дела в порядке</h1>

<div class="page-wrapper">
    <div class="container container--with-sidebar">
        <?= include_template('templates/header.php'); ?>

        <?= include_template('templates/main.php', [
            'current_ts'                      => $current_ts,
            'projects'                        => $projects,
            'tasks'                           => $tasks,
            'current_project_code'            => $current_project_code,
            'get_tasks_count_by_project_code' => $get_tasks_count_by_project_code,
            'get_tasks_by_project_code'       => $get_tasks_by_project_code,
        ]); ?>
    </div>
</div>

<?= include_template('templates/footer.php'); ?>

<?php if ($open_add_task_form): ?>
    <?= include_template('templates/task_addition_form.php', [
        'P_ALL'    => P_ALL,
        'projects' => $projects,
        'fields'   => $_POST,
        'errors'   => $errors_in_new_task,
    ]); ?>
<?php endif; ?>

<script type="text/javascript" src="js/script.js"></script>
</body>
</html>
