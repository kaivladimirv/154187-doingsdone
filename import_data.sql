#Производит добавление проектов
INSERT INTO projects (`name`)
VALUES ('Входящие'), ('Учеба'), ('Работа'), ('Домашние дела'), ('Авто');

#Производит добавление пользователей
INSERT INTO users (`name`, email, password, contacts, date_registration)
VALUES ('Игнат', 'ignat.v@gmail.com', '$2y$10$OqvsKHQwr0Wk6FMZDoHo1uHoXd4UdxJG', '', NOW()),
('Леночка', 'kitty_93@li.ru', '$2y$10$bWtSjUhwgggtxrnJ7rxmIe63ABubHQs0AS0hgnOo41IEdMHkYoSVa', '', NOW()),
('Руслан', 'warrior07@mail.ru', '$2y$10$2OxpEH7narYpkOT1H5cApezuzh10tZEEQ2axgFOaKW.55LxIJBgWW', '', NOW());

#Производит добавление задач
INSERT INTO tasks (`name`, project_code, creator_code, date_creation, date_deadline, path_to_file, date_completion, is_done)
VALUES ('Собеседование в IT компании', 3, 1, NOW(), '2017-06.01', '', NULL, 0),
('Выполнить тестовое задание', 3, 1, NOW(), '2017-05-25', '', NULL, 0),
('Сделать задание первого раздела', 2, 2, NOW(), '2017-04-21', '', '2017-04-20', 1),
('Встреча с другом', 1, 2, NOW(), '2017-04-22', '', NULL, 0),
('Купить корм для кота', 4, 3, NOW(), NULL, '', NULL, 0),
('Заказать пиццу', 4, 3, NOW(), NULL, '', NULL, 0);