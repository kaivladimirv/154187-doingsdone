<?php
require_once 'autoload.php';
require_once 'functions.php';

use Factory\Factory;

$factory = new Factory();

$registerErrors = [];
$loginErrors = [];
$currentUser = [];

if ($factory->auth->isAuthenticated()) {
    $currentUser = $factory->auth->getCurrentUser();
} elseif (isRequestLogin()) {
    $loginErrors = $factory->auth->authenticate();
    if (!is_array($loginErrors)) {
        header('Location: /');
        exit;
    }
}

if (!$currentUser and isRequestRegister()) {
    $registerErrors = $factory->auth->registration();
    if (!is_array($registerErrors)) {
        header('Location: /index.php?login_form=&is_registered');
        exit;
    }
}

$openLoginForm = (isRequestForOpenLoginForm() or $loginErrors);
$openRegisterForm = (!$currentUser and (isRequestForOpenRegisterForm() or $registerErrors));

if ($currentUser and isRequestForShowCompletedTasks()) {
    setShowCompletedTasks();
    header('Location: /');
    exit;
}

$currentProjectCode = getCurrentProjectCode($factory->projects);
if ($currentUser and !$factory->projects->isExists($currentUser['code'], $currentProjectCode)) {
    header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
    exit;
}

$errorsInNewTask = [];
if (isRequestForAddTask()) {
    $errorsInNewTask = $factory->tasks->append($currentUser['code']);
    if (!is_array($errorsInNewTask)) {
        header('Location: /');
        exit;
    }
}

$openAddTaskForm = (isRequestForOpenAddTaskForm() or $errorsInNewTask);

if ($currentUser and isRequestForCompleteTask()) {
    $factory->tasks->complete(getCompleteTaskCode());
}

function getCurrentProjectCode(\Projects\Projects $projects)
{
    return (isset($_GET['project']) ? (int) $_GET['project'] : $projects::ALL_PROJECTS_CODE);
}

function isRequestForOpenAddTaskForm()
{
    return isset($_GET['add']);
}

function isRequestForAddTask()
{
    return isset($_POST['project']);
}

function isRequestForOpenLoginForm()
{
    return isset($_GET['login_form']);
}

function isRequestLogin()
{
    return isset($_GET['login']);
}

function isRequestForOpenRegisterForm()
{
    return isset($_GET['register_form']);
}

function isRequestRegister()
{
    return isset($_GET['register']);
}

function isRequestForShowCompletedTasks()
{
    return isset($_GET['show_completed']);
}

function isRequestForCompleteTask()
{
    return (isset($_GET['complete_task']) and is_numeric($_GET['complete_task']));
}

function getCompleteTaskCode()
{
    return (int) $_GET['complete_task'];
}

function isShowCompletedTasks()
{
    return (isset($_COOKIE['show_completed']) and ($_COOKIE['show_completed'] == 1));
}

function setShowCompletedTasks()
{
    $showCompleted = intval($_GET['show_completed']);

    setcookie('show_completed', $showCompleted, strtotime('+30days'));
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

<body class="<?= (($openAddTaskForm or $openLoginForm) ? 'overlay' : ''); ?>">
<h1 class="visually-hidden">Дела в порядке</h1>

<div class="page-wrapper">
    <div class="container container--with-sidebar">
        <?= includeTemplate('templates/header.php', [
            'currentUser' => $currentUser,
        ]); ?>

        <?php if ($currentUser): ?>
            <?php $tasksByProject = (isShowCompletedTasks() ? $factory->tasks->getListByProject($currentUser['code'], $currentProjectCode) : $factory->tasks->getListOfActiveByProject($currentUser['code'], $currentProjectCode)); ?>
            <?= includeTemplate('templates/main.php', [
                'projects'             => $factory->projects->formatterForDisplay($currentUser['code'], $factory->projects->getListByUser($currentUser['code'])),
                'currentProjectCode'   => $currentProjectCode,
                'tasks'                => $factory->tasks->formatterForDisplay($tasksByProject),
                'isShowCompletedTasks' => isShowCompletedTasks(),
            ]); ?>
        <?php elseif ($openRegisterForm): ?>
            <?= includeTemplate('templates/register.php', ['errors' => $registerErrors]); ?>
        <?php else: ?>
            <?= includeTemplate('templates/guest.php'); ?>
        <?php endif; ?>
    </div>
</div>

<?= includeTemplate('templates/footer.php', [
    'currentUser' => $currentUser,
]); ?>

<?php if ($openAddTaskForm): ?>
    <?= includeTemplate('templates/task_addition_form.php', [
        'projects' => $factory->projects->getListByUser($currentUser['code']),
        'fields'   => $_POST,
        'errors'   => $errorsInNewTask,
    ]); ?>
<?php endif; ?>

<?= includeTemplate('templates/login.php', [
    'openLoginForm' => $openLoginForm,
    'errors'        => $loginErrors,
]); ?>

<script type="text/javascript" src="js/script.js"></script>
</body>
</html>
