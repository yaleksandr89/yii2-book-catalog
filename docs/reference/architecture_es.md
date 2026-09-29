# Arquitectura

## Elegir idioma

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](./architecture.md) | [English](./architecture_en.md) | **Seleccionado** | [中文](./architecture_zh.md) | [Français](./architecture_fr.md) | [Deutsch](./architecture_de.md) |

El proyecto utiliza las funciones habituales de Yii2 sin añadir capas por el mero hecho de tenerlas. ActiveRecord se usa para el acceso ordinario a los datos, los modelos de formulario validan las entradas y las operaciones compuestas se separan cuando necesitan un límite de responsabilidad propio.

## Esquema general

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

Por ejemplo, [`BookController`](../../controllers/BookController.php) recibe la solicitud, comprueba los permisos, obtiene el archivo cargado e inicia la validación. [`BookForm`](../../models/BookForm.php) valida los campos del libro, los autores elegidos y la imagen. Si la validación se completa correctamente, el controlador pasa el cambio a [`BookService`](../../services/BookService.php).

Así, el controlador no gestiona transacciones, el sistema de archivos ni el envío de SMS.

## Datos y relaciones

Los modelos principales son:

- [`Book`](../../models/Book.php) — libro;
- [`Author`](../../models/Author.php) — autor;
- [`Subscription`](../../models/Subscription.php) — suscripción de un número de teléfono a un autor;
- [`User`](../../models/User.php) — usuario que puede modificar el catálogo.

Un libro puede tener varios autores y un autor, varios libros. La relación se guarda en la tabla `book_author`.

La estructura de la base se define mediante [migraciones](../../migrations/). Las claves foráneas protegen las relaciones entre tablas, un índice único impide duplicar una pareja libro–autor y un índice independiente sobre el año de publicación del libro sirve al informe.

Al mostrar la lista de libros, los autores se cargan por adelantado con `with('authors')`, por lo que no se ejecuta una consulta aparte para cada libro.

## Guardar un libro

[`BookService`](../../services/BookService.php) es necesario porque modificar un libro implica varias acciones: guardar el libro, actualizar sus autores, tratar la imagen y notificar a los suscriptores al crearlo.

Al crear un libro:

1. la nueva imagen se guarda con un nombre generado;
2. el libro y sus relaciones con autores se escriben en una sola transacción;
3. si falla la transacción, se revierte y se elimina el archivo nuevo;
4. las notificaciones empiezan después de completar la transacción correctamente.

Al actualizar, se conserva la imagen anterior si no se carga un archivo nuevo. Si se sustituye, el archivo anterior solo se elimina cuando el nuevo estado del libro se ha guardado correctamente.

Al borrar, primero se confirma la eliminación del registro en la base de datos y después se elimina el archivo de imagen.

Así se evita eliminar una imagen válida si el cambio del libro no llega a guardarse en la base de datos.

## Informe Top-10

[`TopAuthorsReportForm`](../../models/TopAuthorsReportForm.php) valida el año elegido y [`TopAuthorsQuery`](../../models/TopAuthorsQuery.php) construye el informe.

MySQL realiza el recuento con una sola consulta agregada: filtra los libros por año, agrupa por autor y selecciona los diez primeros resultados. Cuando varios autores tienen la misma cantidad de libros, la ordenación adicional por nombre e identificador mantiene un orden predecible.

La consulta está separada del controlador porque la agregación es una operación de lectura propia, no una búsqueda habitual de un modelo ActiveRecord.

## Validación y acceso

Las entradas del usuario se validan en el servidor. Por ejemplo, [`BookForm`](../../models/BookForm.php) comprueba los campos obligatorios, el año de publicación, la existencia de los autores elegidos y la imagen cargada: extensiones permitidas, tipo MIME y tamaño máximo de 5 MiB.

Los controladores usan `AccessControl` para las acciones que modifican el catálogo; `VerbFilter` limita la eliminación al método POST. Los formularios web habituales cuentan con protección CSRF de Yii.

Las consultas se construyen con ActiveRecord, Query Builder y condiciones parametrizadas de Yii; los valores del usuario no se insertan en SQL mediante concatenación de cadenas.

Los secretos, incluido `SMSPILOT_API_KEY`, se leen del entorno y de la configuración de la aplicación.

## Decisiones deliberadamente sencillas

ActiveRecord no se envuelve en un repositorio independiente para cada modelo. En esta aplicación, esa capa repetiría principalmente las funciones existentes de Yii sin aportar un aislamiento útil.

Los SMS se envían de forma síncrona después de guardar el libro. Con una carga considerable, sería preferible trasladar el envío a una cola de tareas en segundo plano; Yii2 ofrece la extensión independiente [`yiisoft/yii2-queue`](https://github.com/yiisoft/yii2-queue). Esta aplicación de prueba no añade cola, worker ni infraestructura asociada.

La aplicación sigue siendo una Yii2 Web renderizada en el servidor. No se creó una REST API independiente ni una SPA porque el encargo no las requiere.
