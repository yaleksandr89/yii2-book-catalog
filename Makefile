PROJECT := yii2-book-catalog
ENV_FILE := .env.docker
COMPOSE := docker compose -p $(PROJECT) --env-file $(ENV_FILE)
CMD ?=
-include $(ENV_FILE)
MYSQL_TEST_DATABASE ?= yii2_book_catalog_test
SERVICES := php nginx mysql
SERVICE_TARGETS := restart log in
SERVICE_TARGET := $(firstword $(filter $(SERVICE_TARGETS),$(MAKECMDGOALS)))

ifneq ($(SERVICE_TARGET),)
ifneq ($(firstword $(MAKECMDGOALS)),$(SERVICE_TARGET))
$(error The service target must be the first goal)
endif
SERVICE := $(word 2,$(MAKECMDGOALS))
EXTRA_SERVICE_ARGS := $(wordlist 3,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
ifeq ($(SERVICE),)
$(error Usage: make $(SERVICE_TARGET) <php|nginx|mysql>)
endif
ifneq ($(filter $(SERVICE),$(SERVICES)),$(SERVICE))
$(error Unknown service "$(SERVICE)". Use one of: $(SERVICES))
endif
ifneq ($(EXTRA_SERVICE_ARGS),)
$(error Unexpected extra service arguments: $(EXTRA_SERVICE_ARGS))
endif
.PHONY: $(SERVICE)
$(SERVICE):
	@:
endif

.DEFAULT_GOAL := help
.PHONY: help init check-env cookie-key config build up down restart ps log log-all in smoke db-check php yii composer composer-install migrate demo-data test-db-init test-db-migrate test test-dox coverage coverage-html mysql-reinit check composer-validate php-lint phpstan-check phpcs-check phpcs-fix

help:
	@printf '%s\n' 'Bootstrap / Первичная настройка:'
	@printf '%s\n' '  make init                              Create .env.docker and writable local directories / Создать .env.docker и локальные каталоги с правом записи'
	@printf '%s\n' '  make check-env                         Verify local Docker environment prerequisites / Проверить локальные требования Docker-окружения'
	@printf '%s\n' '  make cookie-key                        Generate a local COOKIE_VALIDATION_KEY / Сгенерировать локальный COOKIE_VALIDATION_KEY'
	@printf '%s\n' ''
	@printf '%s\n' 'Docker lifecycle / Управление Docker:'
	@printf '%s\n' '  make config                            Validate and print Docker Compose config / Проверить и вывести конфигурацию Docker Compose'
	@printf '%s\n' '  make build                             Build PHP development image / Собрать dev-образ PHP'
	@printf '%s\n' '  make up                                Start php, nginx and mysql / Запустить php, nginx и mysql'
	@printf '%s\n' '  make down                              Stop project containers and remove orphans / Остановить контейнеры проекта и удалить orphan-контейнеры'
	@printf '%s\n' '  make restart <php|nginx|mysql>         Restart one running service / Перезапустить один запущенный сервис'
	@printf '%s\n' '  make ps                                Show project containers / Показать контейнеры проекта'
	@printf '%s\n' ''
	@printf '%s\n' 'Interactive / diagnostics / Интерактивная работа и диагностика:'
	@printf '%s\n' '  make in <php|nginx|mysql>              Open a non-root service shell; PHP uses Bash / Открыть shell сервиса без root; PHP использует Bash'
	@printf '%s\n' '  make log <php|nginx|mysql>             Follow logs for one service / Смотреть логи одного сервиса в реальном времени'
	@printf '%s\n' '  make log-all                           Follow project logs / Смотреть все логи проекта в реальном времени'
	@printf '%s\n' '  make smoke                             Check Yii HTTP response through Nginx / Проверить HTTP-ответ Yii через Nginx'
	@printf '%s\n' '  make db-check                          Check MySQL connection through Yii / Проверить подключение к MySQL через Yii'
	@printf '%s\n' ''
	@printf '%s\n' 'PHP / Yii / Composer:'
	@printf '%s\n' '  make php CMD="..."                      Run PHP command in php as app / Запустить PHP-команду в PHP-контейнере от app'
	@printf '%s\n' '  make yii CMD=about                     Run Yii console command in php as app / Запустить Yii Console в PHP-контейнере от app'
	@printf '%s\n' '  make composer CMD="..."                 Run Composer in php as app / Запустить Composer в PHP-контейнере от app'
	@printf '%s\n' '  make composer-install                  Install locked Composer dependencies / Установить Composer-зависимости из lock-файла'
	@printf '%s\n' '  make migrate                           Run Yii database migrations / Применить миграции Yii'
	@printf '%s\n' ''
	@printf '%s\n' 'Development data / Данные для разработки:'
	@printf '%s\n' '  make demo-data                         Append deterministic demo catalog data / Добавить детерминированные демо-данные каталога'
	@printf '%s\n' ''
	@printf '%s\n' 'Tests / Тесты:'
	@printf '%s\n' '  make test-db-init                      Create the isolated MySQL test database / Создать изолированную тестовую БД MySQL'
	@printf '%s\n' '  make test-db-migrate                   Apply migrations to the test database / Применить миграции в тестовую БД'
	@printf '%s\n' '  make test                              Run PHPUnit against the test database / Запустить PHPUnit на тестовой БД'
	@printf '%s\n' '  make test-dox                          Run PHPUnit with readable TestDox output / Запустить PHPUnit с читаемым TestDox-выводом'
	@printf '%s\n' '  make coverage                          Run PHPUnit/Xdebug coverage diagnostic / Запустить диагностику покрытия PHPUnit/Xdebug'
	@printf '%s\n' '  make coverage-html                     Write PHPUnit/Xdebug HTML coverage to runtime/coverage / Записать HTML-отчёт покрытия в runtime/coverage'
	@printf '%s\n' ''
	@printf '%s\n' 'Quality / Качество:'
	@printf '%s\n' '  make check                             Run all configured read-only quality checks / Запустить все настроенные проверки качества без изменения файлов'
	@printf '%s\n' '  make composer-validate                 Validate Composer files / Проверить Composer-файлы'
	@printf '%s\n' '  make php-lint                          Lint first-party PHP files / Проверить синтаксис PHP-файлов проекта'
	@printf '%s\n' '  make phpstan-check                     Run PHPStan read-only analysis / Запустить PHPStan-анализ без изменения файлов'
	@printf '%s\n' '  make phpcs-check                       Run Yii2 PHPCS coding-standard check / Проверить код стандартами Yii2 через PHPCS'
	@printf '%s\n' '  make phpcs-fix                         Auto-fix Yii2 PHPCS coding-standard violations / Автоматически исправить нарушения стандартов Yii2 через PHPCBF'
	@printf '%s\n' ''
	@printf '%s\n' 'Destructive maintenance / Деструктивные операции:'
	@printf '%s\n' '  make mysql-reinit CONFIRM=mysql-data   Recreate MySQL volume and dev/test schemas / Пересоздать MySQL volume и dev/test схемы'
	@printf '%s\n' ''

init:
	@if [ ! -f $(ENV_FILE) ]; then \
		cp .env.docker.example $(ENV_FILE); \
		sed -i "s/^HOST_UID=.*/HOST_UID=$$(id -u)/; s/^HOST_GID=.*/HOST_GID=$$(id -g)/" $(ENV_FILE); \
	else \
		printf '%s\n' 'Existing .env.docker left unchanged.'; \
	fi
	@mkdir -p runtime/cache web/assets web/uploads/books

cookie-key: check-env
	@$(COMPOSE) run --rm --no-deps --user app php php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'

check-env:
	@test -f $(ENV_FILE) || (printf '%s\n' 'Run make init first.' >&2; exit 1)
	@docker compose version

config: check-env
	@$(COMPOSE) config

build: check-env
	@$(COMPOSE) build

up: check-env
	@$(COMPOSE) up -d

down: check-env
	@$(COMPOSE) down --remove-orphans

restart: check-env $(SERVICE)
	@$(COMPOSE) restart $(SERVICE)

ps: check-env
	@$(COMPOSE) ps

log: check-env $(SERVICE)
	@$(COMPOSE) logs -f --tail=100 $(SERVICE)

log-all: check-env
	@$(COMPOSE) logs -f --tail=100

in: check-env $(SERVICE)
	@if [ "$(SERVICE)" = php ]; then \
		if [ -n "$(CMD)" ]; then $(COMPOSE) exec --user app php bash -lc '$(CMD)'; else $(COMPOSE) exec --user app php bash; fi; \
	else \
		if [ -n "$(CMD)" ]; then $(COMPOSE) exec --user $(SERVICE) $(SERVICE) sh -lc '$(CMD)'; else $(COMPOSE) exec --user $(SERVICE) $(SERVICE) sh; fi; \
	fi

smoke: check-env
	@$(COMPOSE) exec --user app php php -r '$$response = file_get_contents("http://nginx/"); if ($$response === false || !str_contains($$response, "Yii")) { fwrite(STDERR, "Yii HTTP smoke failed\n"); exit(1); } echo "Yii HTTP smoke passed\n";'

db-check: check-env
	@$(COMPOSE) exec --user app php php -r 'defined("YII_DEBUG") || define("YII_DEBUG", true); defined("YII_ENV") || define("YII_ENV", "dev"); defined("YII_ENV_DEV") || define("YII_ENV_DEV", true); require "vendor/autoload.php"; require "vendor/yiisoft/yii2/Yii.php"; $$config = require "config/console.php"; $$app = new yii\console\Application($$config); $$app->db->open(); echo "MySQL connection passed\n";'

ifeq ($(filter php,$(SERVICE)),)
php: check-env
	@test -n "$(CMD)" || (echo 'Set CMD, e.g. make php CMD="-v"' >&2; exit 1)
	@$(COMPOSE) exec --user app php php $(CMD)
endif

yii: check-env
	@test -n "$(CMD)" || (echo 'Set CMD, e.g. make yii CMD="about"' >&2; exit 1)
	@$(COMPOSE) exec --user app php ./yii $(CMD)

composer: check-env
	@test -n "$(CMD)" || (echo 'Set CMD, e.g. make composer CMD="validate"' >&2; exit 1)
	@$(COMPOSE) exec --user app php composer $(CMD)

composer-install: check-env
	@$(COMPOSE) exec --user app php composer install --no-interaction --prefer-dist

migrate: check-env
	@$(COMPOSE) exec --user app php ./yii migrate --interactive=0

demo-data: check-env
	@$(COMPOSE) exec --user app php ./yii demo-data/fill

test-db-init: check-env
	@test -n "$(MYSQL_TEST_DATABASE)" || (echo 'MYSQL_TEST_DATABASE must be set in .env.docker.' >&2; exit 1)
	@$(COMPOSE) exec -e MYSQL_TEST_DATABASE=$(MYSQL_TEST_DATABASE) mysql sh -lc 'mysql -uroot -p"$$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS \`$$MYSQL_TEST_DATABASE\`; GRANT ALL PRIVILEGES ON \`$$MYSQL_TEST_DATABASE\`.* TO '\''$$MYSQL_USER'\''@'\''%'\''; FLUSH PRIVILEGES;"'

test-db-migrate: check-env
	@$(COMPOSE) exec --user app \
		-e MYSQL_DATABASE=$(MYSQL_TEST_DATABASE) \
		php ./yii migrate --interactive=0

mysql-reinit: check-env
	@test "$(CONFIRM)" = "mysql-data" || (echo 'Usage: make mysql-reinit CONFIRM=mysql-data' >&2; exit 1)
	@volume="$$( $(COMPOSE) volumes -q mysql )"; \
		test -n "$$volume" || { echo 'MySQL volume not found.' >&2; exit 1; }; \
		$(COMPOSE) down --remove-orphans; \
		docker volume rm "$$volume"
	@$(COMPOSE) up -d --wait
	@$(MAKE) migrate
	@$(MAKE) test-db-init
	@$(MAKE) test-db-migrate

test: check-env
	@$(COMPOSE) exec --user app php ./vendor/bin/phpunit --configuration=phpunit.xml.dist

test-dox: check-env
	@$(COMPOSE) exec --user app php ./vendor/bin/phpunit --configuration=phpunit.xml.dist --testdox

coverage: check-env
	@XDEBUG_MODE=coverage $(COMPOSE) exec --user app -e XDEBUG_MODE php ./vendor/bin/phpunit --configuration=phpunit.xml.dist --coverage-text --show-uncovered-for-coverage-text

coverage-html: check-env
	@$(COMPOSE) exec --user app php rm -rf runtime/coverage
	@XDEBUG_MODE=coverage $(COMPOSE) exec --user app -e XDEBUG_MODE php ./vendor/bin/phpunit --configuration=phpunit.xml.dist --coverage-html runtime/coverage

composer-validate: check-env
	@$(COMPOSE) exec --user app php composer validate

php-lint: check-env
	@$(COMPOSE) exec --user app php bash -lc 'find assets commands config controllers integrations migrations models services tests views web widgets -type f -name "*.php" -print0 | xargs -0 -n1 php -l; php -l yii'

phpstan-check: check-env
	@$(COMPOSE) exec --user app php ./vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --memory-limit=-1

phpcs-check: check-env
	@$(COMPOSE) exec --user app php ./vendor/bin/phpcs --standard=phpcs.xml.dist

phpcs-fix: check-env
	@$(COMPOSE) exec --user app php ./vendor/bin/phpcbf --standard=phpcs.xml.dist

check: composer-validate php-lint phpstan-check phpcs-check
