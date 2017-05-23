<?php
require_once 'mysql_helper.php';
require_once 'functions.php';

define('P_ALL', 0);

$register_errors = [];
$login_errors = [];
$current_user = authentication();

if (!$current_user and is_request_login()) {
    $login_errors = validation_login();
    if (!$login_errors) {
        create_session();
        header('Location: /');
        exit;
    }
}

if (!$current_user and is_request_register()) {
    $register_errors = validation_register();
    if (!$register_errors) {
        if (add_user()) {
            header('Location: /index.php?login_form=&is_registered');
            exit;
        }
    }
}

$open_login_form = ((is_request_for_open_login_form() or $login_errors));
$open_register_form = (!$current_user and (is_request_for_open_register_form() or $register_errors));

if ($current_user and is_request_for_show_completed_tasks()) {
    set_show_completed_tasks();
    header('Location: /');
    exit;
}

$current_project_code = get_current_project_code();
if (!is_exists_project($current_project_code)) {
    header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
    exit;
}

$errors_in_new_task = [];
if (is_request_for_add_task()) {
    $errors_in_new_task = validation_task();
    if (!$errors_in_new_task) {
        if ($task_code = add_task()) {
            if (upload_file($task_code)) {
                header('Location: /');
                exit;
            }

            $errors_in_new_task['preview'] = 'Произошла ошибка при загрузке файла';
        } else {
            $errors_in_new_task['error'] = 'Не удалось создать задачу!';
        }
    }
}

$open_add_task_form = (is_request_for_open_add_task_form() or $errors_in_new_task);

if ($current_user and is_request_for_complete_task()) {
    complete_task(get_complete_task_code());
}

function get_current_project_code()
{
    return (isset($_GET['project']) ? (int) $_GET['project'] : P_ALL);
}

function get_projects_by_user(int $user_code)
{
    $conn_id = db_connection($database_name = 'doingsdone');

    $sql = "SELECT DISTINCT projects.* FROM projects
      JOIN tasks on projects.code = tasks.project_code
      WHERE tasks.creator_code = ?;";

    return db_query($conn_id, $sql, [$user_code]);
}

function is_exists_project(int $project_code)
{
    if ($project_code == P_ALL) {
        return true;
    }

    $conn_id = db_connection($database_name = 'doingsdone');

    $project = db_query($conn_id, "SELECT * FROM projects WHERE (code = ?);", [$project_code]);

    return (is_array($project) and $project);
}

function formatter_projects_for_display(int $user_code, array $projects)
{
    array_unshift($projects, [
        'code' => P_ALL,
        'name' => 'Все',
    ]);

    $projects = array_map(function ($project) use ($user_code) {
        $project['tasks_count'] = get_user_tasks_count_by_project($user_code, $project['code']);

        return $project;
    }, $projects);

    return $projects;
}

function get_user_tasks_by_project(int $user_code, int $project_code)
{
    $where_placeholder = [];
    $where_data = [];
    set_where__tasks_by_user_code($where_placeholder, $where_data, $user_code);
    set_where__tasks_by_project_code($where_placeholder, $where_data, $project_code);

    return get_tasks_by_where($where_placeholder, $where_data);
}

function get_active_user_tasks_by_project(int $user_code, int $project_code)
{
    $where_placeholder = [];
    $where_data = [];

    set_where__tasks_by_user_code($where_placeholder, $where_data, $user_code);
    set_where__active_tasks($where_placeholder, $where_data);
    set_where__tasks_by_project_code($where_placeholder, $where_data, $project_code);

    return get_tasks_by_where($where_placeholder, $where_data);
}

function get_tasks_by_where(array $where_placeholder = [], array $where_data = [])
{
    $where_placeholder = ($where_placeholder ? 'WHERE ' . implode(' AND ', $where_placeholder) : '');

    $conn_id = db_connection($database_name = 'doingsdone');

    return db_query($conn_id, "SELECT * FROM tasks {$where_placeholder};", $where_data);
}

function get_user_tasks_count_by_project(int $user_code, int $project_code)
{
    $where_placeholder = [];
    $where_data = [];
    set_where__tasks_by_user_code($where_placeholder, $where_data, $user_code);
    set_where__tasks_by_project_code($where_placeholder, $where_data, $project_code);

    return get_tasks_count_by_where($where_placeholder, $where_data);
}

function get_tasks_count_by_where(array $where_placeholder = [], array $where_data = [])
{
    $where_placeholder = ($where_placeholder ? 'WHERE ' . implode(' AND ', $where_placeholder) : '');

    $conn_id = db_connection($database_name = 'doingsdone');

    $data = db_query($conn_id, "SELECT COUNT(*) AS tasks_count FROM tasks {$where_placeholder};", $where_data);

    return ($data ? $data[0]['tasks_count'] : 0);
}

function set_where__tasks_by_user_code(array &$where_placeholder, array &$where_data, int $user_code)
{
    $where_placeholder[] = '(creator_code = ?)';
    $where_data[] = $user_code;
}

function set_where__tasks_by_project_code(array &$where_placeholder, array &$where_data, int $project_code)
{
    if ($project_code != P_ALL) {
        $where_placeholder[] = '(project_code = ?)';
        $where_data[] = $project_code;
    }
}

function set_where__active_tasks(array &$where_placeholder, array &$where_data)
{
    $where_placeholder[] = '(is_done = ?)';
    $where_data[] = 0;
}

function formatter_tasks_for_display(array $tasks)
{
    $current_ts = time(); // текущая метка времени

    return array_map(function ($task) use ($current_ts) {
        $task['date_deadline'] = ($task['date_deadline'] ? date('d.m.Y', strtotime($task['date_deadline'])) : '');

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
    return isset($_GET['login_form']);
}

function is_request_login()
{
    return isset($_GET['login']);
}

function is_request_for_open_register_form()
{
    return isset($_GET['register_form']);
}

function is_request_register()
{
    return isset($_GET['register']);
}

function is_request_for_show_completed_tasks()
{
    return isset($_GET['show_completed']);
}

function is_request_for_complete_task()
{
    return (isset($_GET['complete_task']) and is_numeric($_GET['complete_task']));
}

function get_complete_task_code()
{
    return (int) $_GET['complete_task'];
}

function complete_task(int $task_code)
{
    $data = [
        'is_done'         => 1,
        'date_completion' => date('Y-m-d H:i:s', time()),
    ];
    $where = ['code' => $task_code];

    $conn_id = db_connection($database_name = 'doingsdone');

    return db_update($conn_id, 'tasks', $data, $where);
}

function add_task()
{
    $data = [
        'name'          => $_POST['name'],
        'project_code'  => $_POST['project'],
        'creator_code'  => 1,
        'date_creation' => date('Y-m-d H:i:s', time()),
        'date_deadline' => date('Y-m-d H:i:s', strtotime($_POST['date'])),
    ];

    $fields = implode(', ', array_reduce(array_keys($data), function ($carry, $field_name) {
        $carry[] = "`{$field_name}`";

        return $carry;
    }));

    $placeholders = implode(', ', array_fill(0, count($data), '?'));

    $conn_id = db_connection($database_name = 'doingsdone');

    return db_insert($conn_id, "INSERT INTO tasks ({$fields}) VALUES ({$placeholders});", $data);
}

function upload_file(int $task_code)
{
    if (!is_attached_file()) {
        return true;
    }

    $upload_dir = __DIR__ . '/';
    $upload_file = $upload_dir . basename($_FILES['preview']['name']);

    $is_moved = move_uploaded_file($_FILES['preview']['tmp_name'], $upload_file);
    if (!$is_moved) {
        return false;
    }

    $data = ['path_to_file' => $upload_file];
    $where = ['code' => $task_code];

    $conn_id = db_connection($database_name = 'doingsdone');

    return db_update($conn_id, 'tasks', $data, $where);
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

function authentication()
{
    session_start();

    if (!isset($_SESSION['email']) or !isset($_SESSION['password'])) {
        session_write_close();

        return [];
    }

    $user = get_user_by_email($_SESSION['email']);

    if ($user and ($user['password'] == $_SESSION['password'])) {
        return $user;
    }

    session_destroy();

    return [];
}

function create_session()
{
    $user = get_user_by_email($_POST['email']);

    session_start();

    $_SESSION['email'] = $user['email'];
    $_SESSION['password'] = $user['password'];

    return $user;
}

function validation_login()
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

    $user = get_user_by_email($_POST['email']);
    if (!$user) {
        $errors['email'] = 'Пользователя с указанным email-ом не существует';
    } elseif (!password_verify($_POST['password'], $user['password'])) {
        $errors['password'] = 'Вы ввели неверный пароль';
    }

    return $errors;
}

function validation_register()
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

    $user = get_user_by_email($_POST['email']);
    if ($user) {
        $errors['email'] = 'Пользователя с указанным email-ом уже существует';
    }

    return $errors;
}

function add_user()
{
    $data = [
        'name'              => $_POST['name'],
        'email'             => $_POST['email'],
        'password'          => password_hash($_POST['password'], PASSWORD_DEFAULT),
        'date_registration' => date('Y-m-d H:i:s', time()),
    ];

    $fields = create_fields_for_insert(array_keys($data));
    $placeholders = create_placeholders_for_insert(count($data));

    $conn_id = db_connection($database_name = 'doingsdone');

    if ($user_code = db_insert($conn_id, "INSERT INTO users ({$fields}) VALUES ({$placeholders});", $data)) {
        $data['code'] = $user_code;

        return $data;
    }

    return [];
}

function get_users_by_where(array $where_placeholder = [], array $where_data = [])
{
    $where_placeholder = ($where_placeholder ? 'WHERE ' . implode(' AND ', $where_placeholder) : '');

    $conn_id = db_connection($database_name = 'doingsdone');

    return db_query($conn_id, "SELECT * FROM users {$where_placeholder};", $where_data);
}

function get_user_by_email(string $email)
{
    $where_placeholder[] = '(email = ?)';
    $where_data[] = $email;

    $data = get_users_by_where($where_placeholder, $where_data);

    return ($data ? $data[0] : []);
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
            <?php $tasks_by_project = (is_show_completed_tasks() ? get_user_tasks_by_project($current_user['code'], $current_project_code) : get_active_user_tasks_by_project($current_user['code'], $current_project_code)); ?>
            <?= include_template('templates/main.php', [
                'projects'                => formatter_projects_for_display($current_user['code'], get_projects_by_user($current_user['code'])),
                'current_project_code'    => $current_project_code,
                'tasks'                   => formatter_tasks_for_display($tasks_by_project),
                'is_show_completed_tasks' => is_show_completed_tasks(),
            ]); ?>
        <?php elseif ($open_register_form): ?>
            <?= include_template('templates/register.php', ['errors' => $register_errors]); ?>
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
        'projects' => get_projects_by_user($current_user['code']),
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
