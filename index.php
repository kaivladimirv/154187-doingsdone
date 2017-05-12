<?php
require_once 'functions.php';
require_once 'userdata.php';

$login_errors = [];
$current_user = authentication($users);

if (!$current_user and is_request_login()) {
    $login_errors = validation_login($users);
    if (!$login_errors) {
        $current_user = create_session($users);
    }
}

$open_login_form = ((is_request_for_open_login_form() or $login_errors));

if ($current_user and is_request_for_show_completed_tasks()) {
    set_show_completed_tasks();
    header('Location: /');
    exit;
}

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

function get_tasks_by_project_code(array $tasks, int $project_code)
{
    if ($project_code == P_ALL) {
        return $tasks;
    }

    return array_filter($tasks, function ($task) use ($project_code) {
        return ($task['project_code'] == $project_code);
    });
}

function get_active_tasks_by_project_code(array $tasks, int $project_code)
{
    $tasks_by_project = get_tasks_by_project_code($tasks, $project_code);

    return array_filter($tasks_by_project, function ($task) {
        return !$task['is_done'];
    });
}

function formatter_tasks_for_display(array $tasks, int $current_ts)
{
    return array_map(function ($task) use ($current_ts) {
        $task['class_names'] = [];

        if ($task['is_done']) {
            $task['class_names'][] = 'task--completed';
        } elseif ($task['date_deadline']) {
            $days_until_deadline = calc_days_until_task_deadline($task['date_deadline'], $current_ts);
            $task['class_names'][] = ($days_until_deadline <= 0 ? 'task--important' : '');
        }

        return $task;
    }, $tasks);
}

function calc_days_until_task_deadline(string $date_deadline, int $current_ts)
{
    return floor(strtotime($date_deadline) / 86400) - (floor($current_ts / 86400) + 1);
}

function is_request_for_open_add_task_form()
{
    return isset($_GET['add']);
}

function is_request_for_add_task()
{
    return isset($_POST['project']);
}

function is_request_for_open_login_form()
{
    return isset($_GET['login']);
}

function is_request_login()
{
    return (isset($_POST['email']) and isset($_POST['password']));
}

function is_request_for_show_completed_tasks()
{
    return isset($_GET['show_completed']);
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

function authentication(array $users)
{
    session_start();

    if (!isset($_SESSION['email']) or !isset($_SESSION['password'])) {
        session_write_close();

        return [];
    }

    $user = get_user_by_email($users, $_SESSION['email']);

    if ($user and ($user['password'] == $_SESSION['password'])) {
        return $user;
    }

    session_destroy();

    return [];
}

function create_session(array $users)
{
    $user = get_user_by_email($users, $_POST['email']);

    session_start();

    $_SESSION['email'] = $user['email'];
    $_SESSION['password'] = $user['password'];

    return $user;
}

function validation_login(array $users)
{
    $errors = [];

    if (!isset($_POST['email']) or !$_POST['email']) {
        $errors['email'] = 'Не указан email';
    }
    if (!isset($_POST['password']) or !$_POST['password']) {
        $errors['password'] = 'Не указан пароль';
    }
    if ($errors) {
        return $errors;
    }

    $user = get_user_by_email($users, $_POST['email']);
    if (!$user) {
        $errors['email'] = 'Пользователя с указанным email-ом не существует';
    } elseif (!password_verify($_POST['password'], $user['password'])) {
        $errors['password'] = 'Вы ввели неверный пароль';
    }

    return $errors;
}

function get_user_by_email(array $users, string $email)
{
    foreach ($users as $user) {
        if ($user['email'] == $email) {
            return $user;
        }
    }

    return [];
}

function is_show_completed_tasks()
{
    return (isset($_COOKIE['show_completed']) and ($_COOKIE['show_completed'] == 1));
}

function set_show_completed_tasks()
{
    $show_completed = intval($_GET['show_completed']);

    setcookie('show_completed', $show_completed, strtotime('+30days'));
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

<body class="<?= (($open_add_task_form or $open_login_form) ? 'overlay' : ''); ?>">
<h1 class="visually-hidden">Дела в порядке</h1>

<div class="page-wrapper">
    <div class="container container--with-sidebar">
        <?= include_template('templates/header.php', [
            'current_user' => $current_user,
        ]); ?>

        <?php if ($current_user): ?>
            <?php $tasks_by_project = (is_show_completed_tasks() ? get_tasks_by_project_code($tasks, $current_project_code) : get_active_tasks_by_project_code($tasks, $current_project_code)); ?>
            <?= include_template('templates/main.php', [
                'projects'                        => $projects,
                'all_tasks'                       => $tasks,
                'current_project_code'            => $current_project_code,
                'get_tasks_count_by_project_code' => $get_tasks_count_by_project_code,
                'tasks'                           => formatter_tasks_for_display($tasks_by_project, $current_ts),
                'is_show_completed_tasks'         => is_show_completed_tasks(),
            ]); ?>
        <?php else: ?>
            <?= include_template('templates/guest.php'); ?>
        <?php endif; ?>
    </div>
</div>

<?= include_template('templates/footer.php', [
    'current_user' => $current_user,
]); ?>

<?php if ($open_add_task_form): ?>
    <?= include_template('templates/task_addition_form.php', [
        'P_ALL'    => P_ALL,
        'projects' => $projects,
        'fields'   => $_POST,
        'errors'   => $errors_in_new_task,
    ]); ?>
<?php endif; ?>

<?= include_template('templates/login.php', [
    'open_login_form' => $open_login_form,
    'errors'          => $login_errors,
]); ?>

<script type="text/javascript" src="js/script.js"></script>
</body>
</html>
