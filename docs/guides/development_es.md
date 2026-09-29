# Desarrollo

## Elegir idioma

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](./development.md) | [English](./development_en.md) | **Seleccionado** | [中文](./development_zh.md) | [Français](./development_fr.md) | [Deutsch](./development_de.md) |

## Requisitos

En el host se necesitan:

- Git;
- Make;
- Docker con soporte de Compose;
- un editor y utilidades habituales del sistema.

PHP, Composer, Yii CLI, el cliente MySQL, PHPUnit, PHPStan y PHPCS se ejecutan en contenedores mediante los comandos de [`Makefile`](../../Makefile). No es necesario instalarlos por separado en el host.

## Configuración inicial

Para el primer arranque:

```bash
make init
make build
make up
make composer-install
make migrate
```

`make init` crea `.env.docker` a partir de [`.env.docker.example`](../../.env.docker.example) y prepara los directorios locales donde la aplicación escribe durante su ejecución.

Después del arranque, la aplicación está disponible en `http://localhost:8080`.

Para detener los contenedores:

```bash
make down
```

Para crear un usuario con el que iniciar sesión:

```bash
make yii CMD="user/create <username> <password>"
```

## Variables de entorno

Los ajustes principales están en `.env.docker`:

- `HOST_UID`, `HOST_GID` — identificadores del usuario del host;
- `APP_PORT` — puerto por el que Nginx es accesible desde el host;
- `MYSQL_DATABASE` — base de datos principal;
- `MYSQL_TEST_DATABASE` — base de datos de pruebas independiente;
- `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD` — ajustes de MySQL;
- `DB_HOST`, `DB_PORT`, `DB_FORWARD_PORT` — parámetros de conexión a MySQL;
- `COOKIE_VALIDATION_KEY` — clave de validación de cookies;
- `SMSPILOT_API_KEY` — clave de SMSPilot.

Los secretos reales no deben entrar en Git. Sustituye localmente el valor de ejemplo de `COOKIE_VALIDATION_KEY`; el proyecto ofrece `make cookie-key` para generar uno aleatorio. `SMSPILOT_API_KEY` puede quedar vacío si no se comprueba la integración con SMSPilot.

## Contenedores

Docker Compose inicia tres servicios:

- `php` — PHP-FPM y todas las herramientas PHP del proyecto;
- `nginx` — servidor web;
- `mysql` — MySQL 8.4.

El contenedor PHP se ejecuta como usuario `app`. Su UID y GID coinciden con los del usuario del host, por lo que los archivos creados por la aplicación y las herramientas de desarrollo no quedan bajo la propiedad de `root`.

Comandos útiles:

```bash
make ps
make restart php
make log nginx
make in php
```

## Base de datos y datos de demostración

El esquema de la base de datos se crea solo mediante [migraciones Yii](../../migrations/):

```bash
make migrate
```

Las pruebas utilizan una base de datos independiente:

```bash
make test-db-init
make test-db-migrate
```

Para añadir libros y autores de demostración:

```bash
make demo-data
```

Este comando está destinado únicamente al desarrollo local y a comprobaciones manuales repetibles de la interfaz.

## Pruebas y comprobaciones

Comandos principales:

```bash
make test
make test-dox
make check
```

- `make test` ejecuta PHPUnit;
- `make test-dox` ejecuta las mismas pruebas con descripciones legibles de los escenarios;
- `make check` ejecuta sucesivamente comprobaciones de Composer, sintaxis PHP, PHPStan y estilo de código.

Cuando sea necesario, cada comprobación puede ejecutarse por separado:

```bash
make composer-validate
make php-lint
make phpstan-check
make phpcs-check
```

La cobertura se comprueba con un comando separado y no ralentiza el ciclo habitual de desarrollo:

```bash
make coverage
```

Genera un informe Clover en `runtime/coverage.xml`; la versión HTML se obtiene con `make coverage-html` en `runtime/coverage`.

## Comprobaciones automáticas en GitHub Actions

El workflow [`.github/workflows/ci.yml`](../../.github/workflows/ci.yml) repite las comprobaciones principales del proyecto en un entorno limpio:

1. prepara e inicia los contenedores Docker;
2. instala las dependencias desde `composer.lock` y aplica las migraciones;
3. crea una base de datos de pruebas independiente;
4. ejecuta las comprobaciones de código y PHPUnit;
5. envía el informe de cobertura a Codecov;
6. comprueba la conexión a MySQL y la respuesta HTTP de la aplicación a través de Nginx;
7. detiene los contenedores independientemente del resultado de los pasos anteriores.

Este workflow no despliega la aplicación.

## Propiedad de los archivos

Los comandos que pueden crear archivos dentro del proyecto se ejecutan en el contenedor PHP como usuario `app`, asociado al UID/GID del usuario del host. Esto incluye `vendor`, `runtime`, `web/assets` y `web/uploads`.

Los comandos Make existentes bastan para el trabajo habitual. No se necesitan operaciones masivas de `chmod`, `chown` ni borrar directorios para corregir permisos.
