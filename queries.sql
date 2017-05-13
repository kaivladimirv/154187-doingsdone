#Производит выборку проектов по указанному пользователю
SELECT DISTINCT projects.* FROM projects
JOIN tasks on projects.code = tasks.project_code
WHERE tasks.creator_code = 1;

#Производит выборку всех задача указанного проекта
SELECT code, `name`, project_code, creator_code, date_creation, date_deadline, path_to_file, is_done
FROM tasks WHERE project_code = 2;

#Определяет указанную задачу как выполненную
UPDATE tasks SET is_done = 1 WHERE code = 3;

#Производит добавление нового проекта
INSERT INTO projects (`name`) VALUES ('Новый проект');

#Производит добавление новой задачи
INSERT INTO tasks (`name`, project_code, creator_code, date_creation, date_deadline, path_to_file)
VALUES ('Выполнить задание Пишем SQL-запросы', 6, 1, NOW(), '2017-06-01', 'file.doc');

#Производит выборку всех задача завтрашнего дня
SELECT * FROM tasks
WHERE (creator_code = 1)
AND (date_deadline IS NOT NULL)
AND (DATE(date_deadline) = ADDDATE(CURDATE(), 1));

#Производит изменение названия указанной задачи
UPDATE tasks SET `name` = 'Выполнить задание #18 Пишем SQL-запросы' WHERE code = 4;