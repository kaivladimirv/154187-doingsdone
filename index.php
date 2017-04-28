<?php
require_once 'functions.php';

$current_ts = time(); // текущая метка времени

$projects = [
    'Все',
    'Входящие',
    'Учеба',
    'Работа',
    'Домашние дела',
    'Авто',
];
$tasks = [
    [
        'name'          => 'Собеседование в IT компании',
        'date_deadline' => '01.06.2017',
        'project_name'  => 'Работа',
        'is_done'       => false,
    ],
    [
        'name'          => 'Выполнить тестовое задание',
        'date_deadline' => '25.05.2017',
        'project_name'  => 'Работа',
        'is_done'       => false,
    ],
    [
        'name'          => 'Сделать задание первого раздела',
        'date_deadline' => '21.04.2017',
        'project_name'  => 'Учеба',
        'is_done'       => true,
    ],
    [
        'name'          => 'Встреча с другом',
        'date_deadline' => '22.04.2017',
        'project_name'  => 'Входящие',
        'is_done'       => false,
    ],
    [
        'name'          => 'Купить корм для кота',
        'date_deadline' => '',
        'project_name'  => 'Домашние дела',
        'is_done'       => false,
    ],
    [
        'name'          => 'Заказать пиццу',
        'date_deadline' => '',
        'project_name'  => 'Домашние дела',
        'is_done'       => false,
    ],
];

$get_tasks_count_by_project_name = function (array $tasks, string $project_name) {
    if ($project_name == 'Все') {
        return count($tasks);
    }

    $count = 0;
    foreach ($tasks as $task) {
        if ($task['project_name'] == $project_name) {
            $count++;
        }
    }

    return $count;
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

<body><!--class="overlay"-->
<h1 class="visually-hidden">Дела в порядке</h1>

<div class="page-wrapper">
    <div class="container container--with-sidebar">
        <?= include_template('templates/header.php'); ?>

        <?= include_template('templates/main.php', [
            'projects'                        => $projects,
            'tasks'                           => $tasks,
            'current_ts'                      => $current_ts,
            'get_tasks_count_by_project_name' => $get_tasks_count_by_project_name,
        ]); ?>
    </div>
</div>

<?= include_template('templates/footer.php'); ?>

<div class="modal" hidden>
    <button class="modal__close" type="button" name="button">Закрыть</button>

    <h2 class="modal__heading">Добавление задачи</h2>

    <form class="form" class="" action="index.html" method="post">
        <div class="form__row">
            <label class="form__label" for="name">Название <sup>*</sup></label>

            <input class="form__input" type="text" name="name" id="name" value="" placeholder="Введите название">
        </div>

        <div class="form__row">
            <label class="form__label" for="project">Проект <sup>*</sup></label>

            <select class="form__input form__input--select" name="project" id="project">
                <option value="">Входящие</option>
            </select>
        </div>

        <div class="form__row">
            <label class="form__label" for="date">Дата выполнения <sup>*</sup></label>

            <input class="form__input form__input--date" type="text" name="date" id="date" value=""
                   placeholder="Введите дату в формате ДД.ММ.ГГГГ">
        </div>

        <div class="form__row">
            <label class="form__label" for="file">Файл</label>

            <div class="form__input-file">
                <input class="visually-hidden" type="file" name="preview" id="preview" value="">

                <label class="button button--transparent" for="preview">
                    <span>Выберите файл</span>
                </label>
            </div>
        </div>

        <div class="form__row form__row--controls">
            <input class="button" type="submit" name="" value="Добавить">
        </div>
    </form>
</div>

<script type="text/javascript" src="js/script.js"></script>
</body>
</html>
