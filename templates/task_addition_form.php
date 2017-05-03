<div class="modal">
    <button class="modal__close" type="button" name="button">Закрыть</button>

    <h2 class="modal__heading">Добавление задачи</h2>

    <form enctype="multipart/form-data" class="form" action="/index.php" method="post">
        <div class="form__row">
            <label class="form__label" for="name">Название <sup>*</sup></label>

            <input class="form__input <?= (isset($data['errors']['name']) ? 'form__input--error' : ''); ?>" type="text"
                   name="name" id="name"
                   value="<?= (isset($data['fields']['name']) ? $data['fields']['name'] : ''); ?>"
                   placeholder="Введите название">

            <span class="form__error"><?= (isset($data['errors']['name']) ? $data['errors']['name'] : ''); ?></span>
        </div>

        <div class="form__row">
            <label class="form__label" for="project">Проект <sup>*</sup></label>

            <select class="form__input form__input--select <?= (isset($data['errors']['project']) ? 'form__input--error' : ''); ?>"
                    name="project" id="project">
                <option value=""></option>
                <?php foreach ($data['projects'] as $project_code => $project_name): ?>
                    <?php if ($project_code != $data['P_ALL']): ?>
                        <option value="<?= $project_code; ?>"
                            <?= ((isset($data['fields']['project']) and $data['fields']['project'] == $project_code) ? 'selected' : ''); ?>><?= $project_name; ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>

            <span class="form__error"><?= (isset($data['errors']['project']) ? $data['errors']['project'] : ''); ?></span>
        </div>

        <div class="form__row">
            <label class="form__label" for="date">Дата выполнения <sup>*</sup></label>

            <input class="form__input form__input--date <?= (isset($data['errors']['date']) ? 'form__input--error' : ''); ?>"
                   type="text" name="date" id="date"
                   value="<?= (isset($data['fields']['date']) ? $data['fields']['date'] : ''); ?>"
                   placeholder="Введите дату в формате ДД.ММ.ГГГГ">

            <span class="form__error"><?= (isset($data['errors']['date']) ? $data['errors']['date'] : ''); ?></span>
        </div>

        <div class="form__row">
            <label class="form__label" for="file">Файл</label>

            <div class="form__input-file <?= (isset($data['errors']['preview']) ? 'form__input--error' : ''); ?>">
                <input class="visually-hidden" type="file" name="preview" id="preview" value="">

                <label class="button button--transparent" for="preview">
                    <span>Выберите файл</span>
                </label>
            </div>

            <span class="form__error"><?= (isset($data['errors']['preview']) ? $data['errors']['preview'] : ''); ?></span>
        </div>

        <div class="form__row form__row--controls">
            <input class="button" type="submit" name="" value="Добавить">
        </div>
    </form>
</div>