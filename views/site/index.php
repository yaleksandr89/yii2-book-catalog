<?php

/** @var yii\web\View $this */

$this->title = 'Техническое задание — Yii2 Book Catalog';
$this->params['meta_description'] = 'Web-каталог книг на Yii2 и MySQL: авторы, подписки и публичный отчёт.';
$this->params['meta_keywords'] = 'yii2, каталог книг, mysql, php';
?>
<article class="site-index py-4 py-lg-5">
    <header class="rounded-4 border bg-body-tertiary p-4 p-lg-5 mb-5 shadow-sm">
        <p class="text-primary fw-semibold text-uppercase small mb-2">Техническое задание</p>
        <h1 class="display-4 fw-bold mb-3">Каталог книг на Yii2</h1>
        <p class="lead text-body-secondary mb-0 col-lg-10">
            Web-приложение на Yii2 + MySQL для ведения каталога книг и авторов,
            публичного просмотра, подписок на новые книги конкретного автора и
            отчёта по самым продуктивным авторам.
        </p>
    </header>

    <section aria-labelledby="features-heading" class="mb-5">
        <h2 id="features-heading" class="h2 mb-4">Основная функциональность</h2>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="h4 card-title">Книга</h3>
                        <p>Книга содержит:</p>
                        <ul class="mb-3">
                            <li>название;</li>
                            <li>год выпуска;</li>
                            <li>описание;</li>
                            <li>ISBN;</li>
                            <li>главное изображение (обложку).</li>
                        </ul>
                        <p class="mb-0">Одна книга может иметь нескольких авторов.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="h4 card-title">Автор</h3>
                        <p>Автор содержит ФИО.</p>
                        <p class="mb-0">
                            Связь <span lang="en">Book ↔ Author</span> —
                            <span lang="en">many-to-many</span>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section aria-labelledby="access-heading" class="mb-5">
        <h2 id="access-heading" class="h2 mb-4">Права доступа</h2>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card h-100 border-success-subtle">
                    <div class="card-body p-4">
                        <h3 class="h4 card-title">Гость</h3>
                        <p>Неаутентифицированный пользователь может:</p>
                        <ul>
                            <li>просматривать книги;</li>
                            <li>просматривать авторов;</li>
                            <li>открывать публичный отчёт;</li>
                            <li>
                                подписываться по номеру телефона на новые книги
                                конкретного автора.
                            </li>
                        </ul>
                        <p class="mb-0 fw-semibold">Регистрация гостя не требуется.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100 border-primary-subtle">
                    <div class="card-body p-4">
                        <h3 class="h4 card-title">Аутентифицированный пользователь</h3>
                        <p>Может выполнять всё, что доступно гостю, а также:</p>
                        <ul>
                            <li>создавать, редактировать и удалять книги;</li>
                            <li>создавать, редактировать и удалять авторов.</li>
                        </ul>
                        <p class="mb-0">
                            Автор книги — отдельная доменная сущность и не является
                            пользователем приложения.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section aria-labelledby="report-heading" class="mb-5">
        <div class="card bg-primary-subtle border-primary-subtle">
            <div class="card-body p-4 p-lg-5">
                <h2 id="report-heading" class="h2">Отчёт</h2>
                <p>Отдельная публичная web-страница:</p>
                <p class="h4 mb-3">
                    ТОП-10 авторов по количеству выпущенных книг за выбранный год.
                </p>
                <p class="mb-0">Отчёт доступен без авторизации.</p>
            </div>
        </div>
    </section>

    <section aria-labelledby="bonus-heading" class="mb-5">
        <h2 id="bonus-heading" class="h2 mb-4">Дополнительная задача</h2>
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="h4">SMS-уведомления</h3>
                <p>При появлении новой книги подписанные гости получают SMS-уведомление.</p>
                <p>Провайдер: <strong>SMSPilot</strong>.</p>
                <p>
                    Для разработки и проверки используется только emulator/test mode,
                    без реальной отправки SMS.
                </p>
                <p>Подписка оформляется на конкретного автора.</p>
                <p class="mb-0">
                    Функциональность отписки и административного управления подписками
                    не требуется.
                </p>
            </div>
        </div>
    </section>

    <section aria-labelledby="requirements-heading" class="mb-5">
        <h2 id="requirements-heading" class="h2 mb-4">Технические требования и уточнения</h2>
        <dl class="row g-0 border rounded-3 overflow-hidden mb-0">
            <dt class="col-md-4 bg-body-tertiary p-3 border-bottom">Тип приложения</dt>
            <dd class="col-md-8 p-3 border-bottom mb-0">Web, не API.</dd>

            <dt class="col-md-4 bg-body-tertiary p-3 border-bottom">Авторизация</dt>
            <dd class="col-md-8 p-3 border-bottom mb-0">Требуется.</dd>

            <dt class="col-md-4 bg-body-tertiary p-3 border-bottom">Изображения книг</dt>
            <dd class="col-md-8 p-3 border-bottom mb-0">
                Допустим любой разумный вариант хранения; локальное файловое хранилище подходит.
            </dd>

            <dt class="col-md-4 bg-body-tertiary p-3 border-bottom">Отчёт</dt>
            <dd class="col-md-8 p-3 border-bottom mb-0">
                Отдельная публичная web-страница, не PDF.
            </dd>

            <dt class="col-md-4 bg-body-tertiary p-3 border-bottom">Подписка</dt>
            <dd class="col-md-8 p-3 border-bottom mb-0">На конкретного автора.</dd>

            <dt class="col-md-4 bg-body-tertiary p-3 border-bottom">Гость</dt>
            <dd class="col-md-8 p-3 border-bottom mb-0">
                Неаутентифицированный пользователь; для подписки указывает номер телефона.
            </dd>

            <dt class="col-md-4 bg-body-tertiary p-3 border-bottom">CRUD</dt>
            <dd class="col-md-8 p-3 border-bottom mb-0">Требуется для книг и авторов.</dd>

            <dt class="col-md-4 bg-body-tertiary p-3 border-bottom">Отписка</dt>
            <dd class="col-md-8 p-3 border-bottom mb-0">Не требуется.</dd>

            <dt class="col-md-4 bg-body-tertiary p-3 border-bottom">
                Управление подписками администратором
            </dt>
            <dd class="col-md-8 p-3 border-bottom mb-0">Не требуется.</dd>

            <dt class="col-md-4 bg-body-tertiary p-3 border-bottom">База данных</dt>
            <dd class="col-md-8 p-3 border-bottom mb-0">MySQL/MariaDB.</dd>

            <dt class="col-md-4 bg-body-tertiary p-3 border-bottom">Схема БД</dt>
            <dd class="col-md-8 p-3 border-bottom mb-0">
                Только миграции; дамп БД не требуется.
            </dd>

            <dt class="col-md-4 bg-body-tertiary p-3 border-bottom">PHP</dt>
            <dd class="col-md-8 p-3 border-bottom mb-0">8+.</dd>

            <dt class="col-md-4 bg-body-tertiary p-3 border-bottom">Yii2 template</dt>
            <dd class="col-md-8 p-3 border-bottom mb-0">
                Basic или Advanced — по выбору реализации.
            </dd>

            <dt class="col-md-4 bg-body-tertiary p-3 border-bottom">RBAC storage</dt>
            <dd class="col-md-8 p-3 border-bottom mb-0">
                Конкретный механизм не регламентирован.
            </dd>

            <dt class="col-md-4 bg-body-tertiary p-3">Результат</dt>
            <dd class="col-md-8 p-3 mb-0">
                Репозиторий с кодом и инструкцией по запуску; <code>runtime</code> и
                <code>vendor</code> не входят в поставку.
            </dd>
        </dl>
    </section>

    <section aria-labelledby="quality-heading" class="mb-5">
        <h2 id="quality-heading" class="h2 mb-3">Критерии качества</h2>
        <p>При реализации оцениваются:</p>
        <ul class="list-group list-group-flush border rounded-3">
            <li class="list-group-item">отсутствие лишней бизнес-логики в контроллерах;</li>
            <li class="list-group-item">эффективность запросов к БД и работы со связями;</li>
            <li class="list-group-item">соблюдение стандартов кодирования;</li>
            <li class="list-group-item">
                безопасность: валидация данных, работа с API-ключами и секретами;
            </li>
            <li class="list-group-item">точность выполнения требований;</li>
            <li class="list-group-item">качество и воспроизводимость реализации.</li>
        </ul>
    </section>

    <aside aria-labelledby="time-heading" class="alert alert-secondary mb-0 p-4">
        <h2 id="time-heading" class="h3 alert-heading">Исходное ограничение по времени</h2>
        <p>Оценочное время выполнения задания: <strong>3–4 часа</strong>.</p>
        <p>Максимальный лимит, указанный в исходном задании: <strong>8 часов</strong>.</p>
        <p class="mb-0">
            Это описание исходного задания, а не фактическое время реализации проекта.
        </p>
    </aside>
</article>
