<div class="content">
    <section class="content__side">
        <h2 class="content__side-heading">Проекты</h2>

        <nav class="main-navigation">
            <ul class="main-navigation__list">
                <?php foreach ($projects as $key => $project_name): ?>
                    <li class="main-navigation__list-item <?= ($key == 0 ? 'main-navigation__list-item--active' : ''); ?>">
                        <a class="main-navigation__list-item-link" href="#"><?= $project_name; ?></a>
                        <span class="main-navigation__list-item-count"><?= $get_tasks_count_by_project_name($tasks, $project_name); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <a class="button button--transparent button--plus content__side-button" href="#">Добавить проект</a>
    </section>

    <main class="content__main">
        <h2 class="content__main-heading">Список задач</h2>

        <form class="search-form" action="index.php" method="post">
            <input class="search-form__input" type="text" name="" value="" placeholder="Поиск по задачам">

            <input class="search-form__submit" type="submit" name="" value="Искать">
        </form>

        <div class="tasks-controls">
            <div class="radio-button-group">
                <label class="radio-button">
                    <input class="radio-button__input visually-hidden" type="radio" name="radio" checked="">
                    <span class="radio-button__text">Все задачи</span>
                </label>

                <label class="radio-button">
                    <input class="radio-button__input visually-hidden" type="radio" name="radio">
                    <span class="radio-button__text">Повестка дня</span>
                </label>

                <label class="radio-button">
                    <input class="radio-button__input visually-hidden" type="radio" name="radio">
                    <span class="radio-button__text">Завтра</span>
                </label>

                <label class="radio-button">
                    <input class="radio-button__input visually-hidden" type="radio" name="radio">
                    <span class="radio-button__text">Просроченные</span>
                </label>
            </div>

            <label class="checkbox">
                <input id="show-complete-tasks" class="checkbox__input visually-hidden" type="checkbox" checked>
                <span class="checkbox__text">Показывать выполненные</span>
            </label>
        </div>

        <table class="tasks">
            <?php foreach ($tasks as $task): ?>
                <?php $class_name_by_task_status = ''; ?>

                <?php if ($task['is_done']): ?>
                    <?php $class_name_by_task_status = 'task--completed'; ?>
                <?php elseif ($task['date_deadline']): ?>
                    <?php $days_until_deadline = floor(strtotime($task['date_deadline']) / 86400) - (floor($current_ts / 86400) + 1); ?>
                    <?php $class_name_by_task_status = ($days_until_deadline <= 0 ? 'task--important' : ''); ?>
                <?php endif; ?>

                <tr class="tasks__item task <?= $class_name_by_task_status; ?>">
                    <td class="task__select">
                        <label class="checkbox task__checkbox">
                            <input class="checkbox__input visually-hidden"
                                   type="checkbox" <?= ($task['is_done'] ? 'checked' : ''); ?>>
                            <span class="checkbox__text"><?= $task['name']; ?></span>
                        </label>
                    </td>
                    <td class="task__date"><?= $task['date_deadline']; ?></td>

                    <td class="task__controls">
                        <?php if (!$task['is_done']): ?>
                            <button class="expand-control" type="button" name="button">Действия
                            </button>

                            <ul class="expand-list hidden">
                                <li class="expand-list__item">
                                    <a href="#">Выполнить</a>
                                </li>

                                <li class="expand-list__item">
                                    <a href="#">Удалить</a>
                                </li>

                                <li class="expand-list__item">
                                    <a href="#">Дублировать</a>
                                </li>
                            </ul>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </main>
</div>