<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Дела в Порядке!</title>
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="<?= (($data['openAddTaskForm'] or $data['openLoginForm']) ? 'overlay' : ''); ?>">
<h1 class="visually-hidden">Дела в порядке</h1>

<div class="page-wrapper">
    <div class="container container--with-sidebar">
        <?= $data['factory']->templ->includeTemplate('templates/header.php', [
            'currentUser' => $data['currentUser'],
        ]); ?>

        <?php if ($data['currentUser']): ?>
            <?php $tasksByProject = ($data['isShowCompletedTasks'] ? $data['factory']->tasks->getListByProject($data['currentUser']['code'], $data['currentProjectCode']) : $data['factory']->tasks->getListOfActiveByProject($data['currentUser']['code'], $data['currentProjectCode'])); ?>
            <?= $data['factory']->templ->includeTemplate('templates/main.php', [
                'projects'             => $data['factory']->projects->formatterForDisplay($data['currentUser']['code'], $data['factory']->projects->getListByUser($data['currentUser']['code'])),
                'currentProjectCode'   => $data['currentProjectCode'],
                'tasks'                => $data['factory']->tasks->formatterForDisplay($tasksByProject),
                'isShowCompletedTasks' => $data['isShowCompletedTasks'],
            ]); ?>
        <?php elseif ($data['openRegisterForm']): ?>
            <?= $data['factory']->templ->includeTemplate('templates/register.php', ['errors' => $data['registerErrors']]); ?>
        <?php else: ?>
            <?= $data['factory']->templ->includeTemplate('templates/guest.php'); ?>
        <?php endif; ?>
    </div>
</div>

<?= $data['factory']->templ->includeTemplate('templates/footer.php', [
    'currentUser' => $data['currentUser'],
]); ?>

<?php if ($data['openAddTaskForm']): ?>
    <?= $data['factory']->templ->includeTemplate('templates/task_addition_form.php', [
        'projects' => $data['factory']->projects->getListByUser($data['currentUser']['code']),
        'fields'   => $_POST,
        'errors'   => $data['errorsInNewTask'],
    ]); ?>
<?php endif; ?>

<?= $data['factory']->templ->includeTemplate('templates/login.php', [
    'openLoginForm' => $data['openLoginForm'],
    'errors'        => $data['loginErrors'],
]); ?>

<script type="text/javascript" src="js/script.js"></script>
</body>
</html>
