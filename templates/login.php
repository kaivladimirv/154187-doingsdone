<div class="modal" <?= ($data['open_login_form'] ? '' : 'hidden'); ?>>
    <button class="modal__close" type="button" name="button">Закрыть</button>

    <h2 class="modal__heading">
        <?php if (isset($_GET['is_registered'])) : ?>
            Теперь вы можете войти, используя свой email и пароль
        <?php else: ?>
            Вход на сайт
        <?php endif; ?>
    </h2>

    <form class="form" action="/index.php?login" method="post">
        <div class="form__row">
            <label class="form__label" for="email">E-mail <sup>*</sup></label>

            <input class="form__input <?= (isset($data['errors']['email']) ? 'form__input--error' : ''); ?>" type="text"
                   name="email" id="email" value="<?= (isset($_POST['email']) ? $_POST['email'] : ''); ?>"
                   placeholder="Введите e-mail">

            <p class="form__message"><?= (isset($data['errors']['email']) ? $data['errors']['email'] : ''); ?></p>
        </div>

        <div class="form__row">
            <label class="form__label" for="password">Пароль <sup>*</sup></label>

            <input class="form__input <?= (isset($data['errors']['password']) ? 'form__input--error' : ''); ?>"
                   type="password" name="password" id="password" value=""
                   placeholder="Введите пароль">
            <p class="form__message"><?= (isset($data['errors']['password']) ? $data['errors']['password'] : ''); ?></p>
        </div>

        <div class="form__row">
            <label class="checkbox">
                <input class="checkbox__input visually-hidden" type="checkbox" checked>
                <span class="checkbox__text">Запомнить меня</span>
            </label>
        </div>

        <div class="form__row form__row--controls">
            <input class="button" type="submit" name="" value="Войти">
        </div>
    </form>
</div>
