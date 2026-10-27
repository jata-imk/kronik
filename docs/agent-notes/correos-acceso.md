# Correos de acceso y seguridad

## Referencias

- Notion: https://app.notion.com/p/3fd61db7db7f8154a184fe116db8e0d5.
- Rama: `feat/correos-transaccionales`, desde `origin/main` (`0a7656c`).
- PR: [#40](https://github.com/jata-imk/kronik/pull/40), integrada en `main` mediante `06a5723`.
- Guía: [Probar correos de acceso](../how-to/probar-correos-acceso.md).

## Objetivo

Aplicar formato compartido y textos españoles a invitación, recuperación y verificación. Autorizado por el usuario después del reset exitoso de QA. Mantener credenciales en QA y compatibilidad con Outlook clásico.

## Estado actual

- Estado: integrado, desplegado y con invitaciones y revisión visual reportadas como satisfactorias en QA.
- Actualización: 2026-10-18.
- Callbacks oficiales de Laravel, sin reemplazar notificaciones, broker ni firma temporal. Invitación identificada por `UserStatus::Pending`.
- Una vista HTML con tablas, estilos en línea, contenedor MSO y botón VML; texto plano alternativo con enlaces exactos.
- `mail:preview` genera HTML/TXT/EML ficticios privados, solo local/testing, sin enviar ni tocar usuarios.
- Durante el desarrollo no se copiaron credenciales QA al entorno local ni se enviaron mensajes reales. Después del despliegue, el usuario confirmó envío, revisión de logs, recepción e invitaciones en QA.

## Evidencia

- Commit `605020b`: 23 pruebas focalizadas / 166 aserciones; regresión final del hook pre-push: 416 aprobadas, una omitida y 40,392 aserciones. [CI con MariaDB/frontend aprobado](https://github.com/jata-imk/kronik/actions/runs/39106007757).
- Pint en los cinco archivos PHP: aprobado.
- Playwright MCP: tres variantes a 320 px, sin desbordamiento horizontal; invitación en navegador maximizado y captura completa. Se usa otro contexto para móvil, sin fijar dimensiones al navegador compartido.
- Capturas completas en `storage/framework/testing/mail-*.png` (ignoradas por Git); dos muestras de escritorio/móvil incluidas en `docs/assets/correos/`.
- Migraciones, seeders y variables nuevas: ninguna.
- Confirmación del usuario el 2026-10-18: recepción de correos, creación de usuario mediante invitación y visualización correcta en Outlook clásico. No se informó versión/build, sistema ni captura; no se presume una matriz completa de clientes o variantes.

## Siguiente paso

Usar la guía operativa para nuevas altas y registrar cualquier incidencia con
entorno y versión del cliente. Si se requiere ampliar la evidencia, comprobar
manualmente recuperación/verificación y otras variantes visuales; esas
confirmaciones específicas no fueron reportadas.

## Cierre

- Commit/PR: `605020b`, PR #40 integrada mediante `06a5723`.
- Entrega SMTP y revisión de Outlook clásico del correo observado: confirmadas por el usuario. Se retiran esos pendientes generales; las variantes no reportadas conservan su alcance de prueba automatizada. No se contrató un servicio externo de capturas.
