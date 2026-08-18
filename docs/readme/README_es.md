# Catálogo de libros en Yii2

[![Source Code](https://img.shields.io/badge/source-yaleksandr89%2Fyii2--book--catalog-blue.svg?style=flat-square)](https://github.com/yaleksandr89/yii2-book-catalog)
[![CI](https://img.shields.io/github/actions/workflow/status/yaleksandr89/yii2-book-catalog/ci.yml?style=flat-square&label=CI)](https://github.com/yaleksandr89/yii2-book-catalog/actions/workflows/ci.yml)
[![Codecov](https://codecov.io/gh/yaleksandr89/yii2-book-catalog/graph/badge.svg)](https://codecov.io/gh/yaleksandr89/yii2-book-catalog)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4.svg?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![Yii](https://img.shields.io/badge/Yii-2.0.55-40B3D8.svg?style=flat-square)](https://www.yiiframework.com/)
[![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1.svg?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED.svg?style=flat-square&logo=docker&logoColor=white)](https://www.docker.com/)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](../../LICENSE)

<p align="center">
  <img
    src="../assets/yii2-book-catalog-readme-cover.png"
    alt="Yii2 Book Catalog — web catalog with authors, subscriptions, Top-10 report and SMSPilot"
    width="100%"
  >
</p>

## Elige un idioma

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](../../README.md) | [English](./README_en.md) | **Seleccionado** | [中文](./README_zh.md) | [Français](./README_fr.md) | [Deutsch](./README_de.md) |

Aplicación web de prueba con Yii2 y MySQL: catálogo de libros y autores con carga de portadas, relación muchos a muchos, ranking público de autores, suscripciones por teléfono e integración de prueba con SMSPilot.

La implementación se centra en la separación de responsabilidades: los controladores gestionan las solicitudes HTTP y el acceso, los datos de entrada se validan en el servidor, las operaciones compuestas sobre libros se delegan en un servicio independiente y el informe se construye con una única consulta agregada. El proyecto se ejecuta en Docker y no requiere PHP ni Composer en el host.

## Funcionalidades

- catálogo público de libros y autores;
- creación, edición y eliminación de libros y autores después de iniciar sesión;
- carga de la imagen principal de un libro;
- varios autores por libro y varios libros por autor;
- Top-10 público de autores por número de libros para un año seleccionado;
- suscripción de invitados por número de teléfono a un autor concreto;
- notificaciones SMS de prueba sobre nuevos libros mediante SMSPilot.

## Inicio rápido

```bash
make init
make build
make up
make composer-install
make migrate
```

Después del inicio, la aplicación está disponible en [http://localhost:8080](http://localhost:8080).

Puedes crear un usuario para iniciar sesión con el comando de consola:

```bash
make yii CMD="user/create <username> <password>"
```

Usa `make demo-data` para llenar el catálogo con datos de demostración. El resto de comandos para el entorno, la base de datos de pruebas y las comprobaciones están en la [guía de desarrollo](../development.md).

## Acceso

| Usuario | Capacidades |
| --- | --- |
| Invitado | Ver libros y autores, consultar el Top-10 de un año seleccionado y suscribirse por teléfono a un autor |
| Usuario autenticado | Todo lo disponible para un invitado, además de crear, editar y eliminar libros y autores |

## Estructura de la aplicación

```text
solicitud HTTP
    ↓
controlador
    ↓
modelo de formulario
    ↓
servicio / ActiveRecord / consulta dedicada del informe
    ↓
MySQL
```

Los controladores se mantienen pequeños y se ocupan principalmente del escenario web: recibir la solicitud, comprobar el acceso, ejecutar la validación y delegar el trabajo. [`BookForm`](../../models/BookForm.php) valida los datos del libro, mientras que [`BookService`](../../services/BookService.php) guarda el libro, sus relaciones con autores y la imagen.

Para el Top-10 existe [`TopAuthorsQuery`](../../models/TopAuthorsQuery.php): el cálculo se realiza directamente en la base de datos con una sola consulta, en lugar de construirse en PHP a partir de modelos cargados.

Estas decisiones, el manejo de imágenes y los límites de responsabilidad se explican en la [guía de arquitectura](../architecture.md).

## SMSPilot

Después de crear correctamente un libro, la aplicación encuentra a los suscriptores de sus autores y envía notificaciones mediante SMSPilot en modo de prueba. El envío comienza solo después de guardar el libro y sus relaciones en la base de datos, por lo que un fallo del servicio externo no revierte un libro ya creado. Si el mismo número está suscrito a varios autores del nuevo libro, solo se realiza un intento de envío para ese número.

La verificación manual mostró que un mensaje con un título largo en cirílico era calculado por el emulador como un SMS multipart más caro: `19.74` frente a `9.87` después de acortar el texto. Por eso se eliminó el título del libro de la notificación y el mensaje quedó limitado a dos variantes cortas para uno o varios autores coincidentes.

Las respuestas de SMSPilot, el orden de envío y el manejo de errores se describen en la [guía de integración](../smspilot.md).

## Lo que se mantiene simple intencionadamente

- Los SMS se envían de forma síncrona después de guardar el libro. En una aplicación con carga significativa, este trabajo normalmente se movería fuera de la solicitud HTTP a una cola de tareas en segundo plano, por ejemplo [`yiisoft/yii2-queue`](https://github.com/yiisoft/yii2-queue). Para esta prueba no se añadieron un worker de cola ni infraestructura adicional.
- ActiveRecord se usa directamente cuando Yii ya ofrece suficiente funcionalidad para lecturas y escrituras habituales. No se añadió una capa de repositorios alrededor de cada modelo porque, para una aplicación de este tamaño, duplicaría en gran medida la capa existente de acceso a datos.
- No se añadieron una REST API independiente ni una SPA cliente: la aplicación está implementada como una aplicación web Yii2 renderizada en el servidor.
