#Производит выборку всех проектов
SELECT * FROM projects;

#Производит выборку всех задача указанного проекта
SELECT * FROM tasks WHERE project_code = 2;

#Определяет указанную задачу как выполненную
UPDATE tasks SET is_done = 1 WHERE code = 3;

#Производит добавление нового проекта
INSERT INTO projects (`name`) VALUES ('Новый проект');

#Производит добавление новой задачи
INSERT INTO tasks (`name`, project_code, creator_code, date_creation, date_deadline, path_to_file)
VALUES ('Выполнить задание Пишем SQL-запросы', 6, 1, NOW(), '2017-06-01', 'file.doc');

#Производит выборку всех задача завтрашнего дня
SELECT * FROM tasks
WHERE (date_deadline IS NOT NULL)
AND (DATE(date_deadline) = ADDDATE(CURDATE(), 1));

#Производит изменение названия указанной задачи
UPDATE tasks SET `name` = 'Выполнить задание #18 Пишем SQL-запросы' WHERE code = 4;