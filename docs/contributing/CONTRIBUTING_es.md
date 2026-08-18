# Contribuir

## Elige un idioma

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](../../.github/CONTRIBUTING.md) | [English](./CONTRIBUTING_en.md) | **Español** | [中文](./CONTRIBUTING_zh.md) | [Français](./CONTRIBUTING_fr.md) | [Deutsch](./CONTRIBUTING_de.md) |

Gracias por tu interés en Yii2 Book Catalog. Es una aplicación web Yii2 pequeña, por lo que los cambios deben mantenerse acotados, reproducibles y fáciles de revisar.

## Antes de empezar

- Informa de un error reproducible mediante un GitHub Issue.
- Para una mejora, describe el problema, el caso de uso y el comportamiento esperado.
- Para un problema de seguridad, sigue la [política de seguridad](../../.github/SECURITY.md) y no publiques detalles sensibles.
- Antes de un cambio grande, comprueba que encaja con el propósito del proyecto y que no amplía el alcance sin una razón clara.

## Contrato de la aplicación

- El proyecto es una aplicación web Yii2, no una REST API, SPA ni plataforma de producción.
- `Book` y `Author` tienen una relación muchos a muchos.
- Los invitados pueden navegar por el catálogo, usar el Top-10 público y suscribirse por teléfono a un autor concreto.
- Los usuarios autenticados también pueden gestionar libros y autores.
- El flujo principal es `Controller → Form Model / DTO → application service → ActiveRecord / focused query → DB`.
- `BookService` se inyecta en `BookController` mediante Yii DI; el controlador no construye por sí mismo servicios de aplicación ni dependencias externas de provider/client.
- Los cambios de esquema de base de datos se realizan solo mediante migrations.
- SMSPilot se usa únicamente en modo emulator/test.
- Los secretos, API keys y valores locales del entorno se proporcionan mediante environment/config y no se incluyen en commits.

## Ramas

Usa un nombre corto que refleje el propósito del cambio, por ejemplo:

```text
fix/book-validation
docs/update-development-guide
chore/update-ci
```

## Commits

Se recomienda Conventional Commits. Ejemplos:

```text
fix: correct book validation
docs: clarify local startup
test: cover subscription regression
chore: update CI configuration
```

## Comprobaciones locales

El runtime del proyecto usa Make targets respaldados por Docker. No ejecutes PHP, Composer, Yii CLI, PHPUnit, PHPStan ni PHPCS en el host.

Las instrucciones del primer arranque están en la [guía de desarrollo](../development.md).

Antes de un Pull Request, ejecuta:

```shell
make check
```

Si cambia el comportamiento de la aplicación, ejecuta también:

```shell
make test
```

La cobertura es un diagnóstico separado y solo se ejecuta cuando es relevante:

```shell
make coverage
```

Para cambios del esquema de la base de datos, añade una migration y verifica el flujo correspondiente. No uses `chmod`, `chown` amplios ni eliminación de estado generado para ocultar problemas del entorno.

## Pull Request

En la descripción del Pull Request indica:

- el problema y el cambio realizado;
- las comprobaciones ejecutadas;
- los tests añadidos o actualizados si cambia el comportamiento;
- el impacto en la base de datos y los detalles de la migration si cambia el esquema;
- el impacto en documentación, UI, uploads o integraciones externas.

Antes de enviar, comprueba que:

- no se incluyen secretos, API keys, cookies, session data ni `.env*` locales;
- `vendor/`, `runtime/`, assets generados, uploads y resultados de coverage no entran en el commit;
- el cambio se limita a una única tarea coherente;
- no hay formatting o refactoring no relacionados;
- la documentación coincide con el comportamiento verificado.
