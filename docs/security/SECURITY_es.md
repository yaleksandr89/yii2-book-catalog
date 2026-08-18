# Política de seguridad

## Elige un idioma

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](../../.github/SECURITY.md) | [English](./SECURITY_en.md) | **Español** | [中文](./SECURITY_zh.md) | [Français](./SECURITY_fr.md) | [Deutsch](./SECURITY_de.md) |

## Versiones compatibles

Las correcciones de seguridad se consideran para el estado actual de `master` y la última versión publicada.

| Versión | Soporte |
|---|---|
| `master` | Sí |
| Última versión publicada | Sí |

## Qué se considera una vulnerabilidad

Entre los problemas de seguridad se incluyen, en particular:

- eludir la autenticación, `AccessControl` o las restricciones de acciones destructivas;
- eludir la protección CSRF;
- manejo inseguro de archivos subidos, nombres de archivo o rutas;
- SQL injection o evasión de la validación del lado del servidor;
- exposición de API keys, contraseñas, cookies, session data u otra configuración privada;
- filtración de datos sensibles mediante logs, mensajes de error o la integración SMSPilot;
- acceso no autorizado a datos de otro usuario o a una acción protegida.

Los errores ordinarios, preguntas de uso y propuestas de mejora pueden publicarse en GitHub Issues si no contienen datos sensibles.

## Cómo informar de una vulnerabilidad

Usa preferentemente GitHub Private Vulnerability Reporting cuando esté disponible:

1. Abre la pestaña **Security** del repositorio.
2. Ve a **Advisories**.
3. Selecciona **Report a vulnerability**.
4. Envía el informe sin publicar detalles sensibles en un Issue normal.

Si Private Vulnerability Reporting no está disponible, crea un Issue público mínimo sin detalles técnicos de la vulnerabilidad y solicita un canal privado de contacto.

No publiques antes de que exista una corrección:

- API keys o contraseñas;
- cookies, session data o CSRF tokens;
- datos personales reales;
- logs completos de producción;
- un exploit funcional o detalles innecesarios que permitan reproducir el ataque.

## Qué incluir

Cuando sea posible, incluye:

- release, branch o commit afectado;
- impacto;
- pasos mínimos de reproducción;
- comportamiento esperado y real;
- fragmentos saneados de request/response/log si ayudan;
- una posible corrección, si la conoces.

Usa únicamente datos sintéticos o anonimizados.

## Gestión del informe

Los informes se revisan según la disponibilidad; no se promete un SLA fijo.

Coordina la divulgación con el mantenedor antes de publicar detalles. Tras confirmar una vulnerabilidad, la corrección y la información sobre versiones afectadas se publican mediante divulgación coordinada.

El proyecto no anuncia un programa de recompensas por vulnerabilidades.
