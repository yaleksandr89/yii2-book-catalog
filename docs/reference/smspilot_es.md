# SMSPilot

## Elegir idioma

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](./smspilot.md) | [English](./smspilot_en.md) | **Seleccionado** | [中文](./smspilot_zh.md) | [Français](./smspilot_fr.md) | [Deutsch](./smspilot_de.md) |

Al crear un libro nuevo, la aplicación envía SMS a los visitantes suscritos a uno de sus autores. La integración funciona solo en el modo de prueba de SMSPilot: las solicitudes pasan por el servicio, pero no se realiza ninguna entrega real al operador móvil.

## Cómo funciona el envío

```text
BookService
    ↓
SmsSenderInterface
    ↓
SmsPilotSender
    ↓
SMSPilot
```

[`BookService`](../../services/BookService.php) depende de la pequeña interfaz [`SmsSenderInterface`](../../integrations/SmsSenderInterface.php); [`SmsPilotSender`](../../integrations/smspilot/SmsPilotSender.php) se encarga del servicio externo concreto.

[`SmsPilotSendResponse`](../../integrations/smspilot/SmsPilotSendResponse.php) comprueba que las respuestas correctas de SMSPilot tengan la estructura esperada. La clave del servicio se proporciona mediante la configuración de la aplicación y no se guarda en el código fuente.

`SmsPilotSender` fuerza `test=1` en cada solicitud. También establece un tiempo de espera de red corto y desactiva el registro del contenido de la respuesta HTTP.

## Cuándo se envía el SMS

El envío queda fuera de la transacción que guarda el libro.

La secuencia es:

1. se guarda la imagen;
2. el libro y sus relaciones con autores se escriben en la base de datos;
3. la transacción termina correctamente;
4. solo entonces se seleccionan los suscriptores y comienzan las solicitudes a SMSPilot.

Por eso, la falta de disponibilidad de SMSPilot no puede deshacer un libro ya creado.

Si el servicio externo devuelve un error para un número, [`BookService`](../../services/BookService.php) registra una advertencia y continúa con los demás destinatarios.

## Cómo se eligen los destinatarios

Los destinatarios se seleccionan mediante una sola consulta agregada por números de teléfono.

Si un número está suscrito a varios autores del libro nuevo, aparece una sola vez en el resultado y recibe como máximo un intento de envío. Esto evita notificaciones duplicadas y no requiere una consulta a la base de datos por cada autor.

Los números se ordenan para mantener predecible el orden de procesamiento.

## Por qué se acortó el mensaje

La primera versión funcional incluía el título del libro. Funcionaba: en una comprobación manual, SMSPilot devolvió HTTP 200 y el estado de prueba correcto `0`. La respuesta del emulador incluía estos valores:

```text
server_id = 10000
status    = 0
price     = 19.74
cost      = 19.74
balance   = 60.89
```

No hubo entrega real: la solicitud usó `test=1`.

La comprobación mostró que un título largo en cirílico convertía el texto en un SMS multipart. Como el usuario determina la longitud del título, tanto el número de segmentos como el coste calculado por el emulador podían crecer con ella.

Se retiró el título de la notificación y quedaron dos variantes limitadas:

```text
Новая книга у автора: <имя автора>.
```

Cuando coinciden varios autores:

```text
Новая книга у авторов из ваших подписок.
```

Una segunda comprobación manual dio estos resultados:

| Escenario | Intentos de envío por número | Estado SMSPilot | `price` | `cost` |
| --- | ---: | ---: | ---: | ---: |
| Un autor coincidente | 1 | 0 | 9.87 | 9.87 |
| Dos autores coincidentes, ambos suscritos | 1 | 0 | 9.87 | 9.87 |

En este escenario verificado, el coste calculado por el emulador bajó de `19.74` a `9.87`, exactamente a la mitad. Es el resultado de una comprobación concreta en modo de prueba, no una afirmación sobre las tarifas de SMSPilot ni el coste de una entrega real.

La segunda comprobación también confirmó la deduplicación: aunque un número estuviera suscrito a dos autores del libro nuevo, solo hubo un intento de envío.

## Manejo de errores

[`SmsPilotSender`](../../integrations/smspilot/SmsPilotSender.php) convierte los errores de red, las respuestas HTTP fallidas, el JSON inválido y el rechazo de SMSPilot en una `RuntimeException` con un mensaje seguro.

[`BookService`](../../services/BookService.php) captura ese error después de guardar el libro. En el registro queda una advertencia breve sin la clave API ni la respuesta original del proveedor; se sigue procesando a los siguientes destinatarios.

Así, un fallo de notificación sigue siendo un error de la integración externa y no daña el estado del catálogo.

## Si aumenta el volumen de envíos

Actualmente las solicitudes a SMSPilot se envían en secuencia dentro de la misma solicitud HTTP que crea el libro. Para una pequeña aplicación de prueba, esto evita infraestructura adicional.

Con mayor carga, sería mejor trasladar el envío a una cola de tareas en segundo plano. En Yii2 se puede usar, por ejemplo, [`yiisoft/yii2-queue`](https://github.com/yiisoft/yii2-queue). Si también se necesitan garantías de reintento, el estado de las notificaciones salientes debería guardarse por separado en la base de datos y un worker independiente debería realizar el envío.

Con muchos destinatarios, también conviene considerar el envío por lotes del proveedor SMS para reducir el número de solicitudes HTTP individuales.
