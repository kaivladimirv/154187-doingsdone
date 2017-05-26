<?php
require_once 'autoload.php';

use Factory\Factory;

$factory = new Factory();

$registerErrors = [];
$loginErrors = [];
$currentUser = [];

if ($factory->auth->isAuthenticated()) {
    $currentUser = $factory->auth->getCurrentUser();
} elseif ($factory->request->isRequestLogin()) {
    $loginErrors = $factory->auth->authenticate();
    if (!is_array($loginErrors)) {
        header('Location: /');
        exit;
    }
}

if (!$currentUser and $factory->request->isRequestRegister()) {
    $registerErrors = $factory->auth->registration();
    if (!is_array($registerErrors)) {
        header('Location: /index.php?login_form=&is_registered');
        exit;
    }
}

$openLoginForm = ($factory->request->isRequestForOpenLoginForm() or $loginErrors);
$openRegisterForm = (!$currentUser and ($factory->request->isRequestForOpenRegisterForm() or $registerErrors));

if ($currentUser and $factory->request->isRequestForShowCompletedTasks()) {
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
if ($factory->request->isRequestForAddTask()) {
    $errorsInNewTask = $factory->tasks->append($currentUser['code']);
    if (!is_array($errorsInNewTask)) {
        header('Location: /');
        exit;
    }
}

$openAddTaskForm = ($factory->request->isRequestForOpenAddTaskForm() or $errorsInNewTask);

if ($currentUser and $factory->request->isRequestForCompleteTask()) {
    $factory->tasks->complete(getCompleteTaskCode());
}

function getCurrentProjectCode(\Projects\Projects $projects)
{
    return (isset($_GET['project']) ? (int) $_GET['project'] : $projects::ALL_PROJECTS_CODE);
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
        <?= $factory->templ->includeTemplate('templates/header.php', [
            'currentUser' => $currentUser,
        ]); ?>

        <?php if ($currentUser): ?>
            <?php $tasksByProject = (isShowCompletedTasks() ? $factory->tasks->getListByProject($currentUser['code'], $currentProjectCode) : $factory->tasks->getListOfActiveByProject($currentUser['code'], $currentProjectCode)); ?>
            <?= $factory->templ->includeTemplate('templates/main.php', [
                'projects'             => $factory->projects->formatterForDisplay($currentUser['code'], $factory->projects->getListByUser($currentUser['code'])),
                'currentProjectCode'   => $currentProjectCode,
                'tasks'                => $factory->tasks->formatterForDisplay($tasksByProject),
                'isShowCompletedTasks' => isShowCompletedTasks(),
            ]); ?>
        <?php elseif ($openRegisterForm): ?>
            <?= $factory->templ->includeTemplate('templates/register.php', ['errors' => $registerErrors]); ?>
        <?php else: ?>
            <?= $factory->templ->includeTemplate('templates/guest.php'); ?>
        <?php endif; ?>
    </div>
</div>

<?= $factory->templ->includeTemplate('templates/footer.php', [
    'currentUser' => $currentUser,
]); ?>

<?php if ($openAddTaskForm): ?>
    <?= $factory->templ->includeTemplate('templates/task_addition_form.php', [
        'projects' => $factory->projects->getListByUser($currentUser['code']),
        'fields'   => $_POST,
        'errors'   => $errorsInNewTask,
    ]); ?>
<?php endif; ?>

<?= $factory->templ->includeTemplate('templates/login.php', [
    'openLoginForm' => $openLoginForm,
    'errors'        => $loginErrors,
]); ?>

<script type="text/javascript" src="js/script.js"></script>
</body>
</html>
