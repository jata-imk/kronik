# Correos de acceso y seguridad

## Referencias

- Notion: https://app.notion.com/p/3fd61db7db7f8154a184fe116db8e0d5.
- Rama: `feat/correos-transaccionales`, desde `origin/main` (`0a7656c`).
- PR y evidencia final: consultar el seguimiento de Notion anterior.
- Guía: [Probar correos de acceso](../how-to/probar-correos-acceso.md).

## Objetivo

Aplicar formato compartido y textos españoles a invitación, recuperación y verificación. Autorizado por el usuario después del reset exitoso de QA. Mantener credenciales en QA y compatibilidad con Outlook clásico.

## Estado actual

- Estado: implementado, pendiente de revisión e integración.
- Actualización: 2026-10-18.
- Callbacks oficiales de Laravel, sin reemplazar notificaciones, broker ni firma temporal. Invitación identificada por `UserStatus::Pending`.
- Una vista HTML con tablas, estilos en línea, contenedor MSO y botón VML; texto plano alternativo con enlaces exactos.
- `mail:preview` genera HTML/TXT/EML ficticios privados, solo local/testing, sin enviar ni tocar usuarios.
- `.env` y datos locales/QA sin cambios. No se enviaron mensajes reales.

## Evidencia

- 23 pruebas de correo/usuarios/perfil/recuperación/verificación aprobadas. Regresión general: 416 aprobadas, una omitida, 40,391 aserciones; la PR registra el total final después de agregar la comprobación de paridad del enlace VML.
- Pint en los cinco archivos PHP: aprobado.
- Playwright MCP: tres variantes a 320 px, sin desbordamiento horizontal; invitación en navegador maximizado y captura completa. Se usa otro contexto para móvil, sin fijar dimensiones al navegador compartido.
- Capturas completas en `storage/framework/testing/mail-*.png` (ignoradas por Git); dos muestras de escritorio/móvil incluidas en `docs/assets/correos/`.
- Migraciones, seeders y variables nuevas: ninguna.

## Siguiente paso

Revisar e integrar la PR; después del despliegue, validar entrega desde QA a buzón autorizado y renderizado en Outlook clásico nativo. Chromium no certifica Word/Outlook; esta comprobación queda pendiente porque el usuario no dispone de ese cliente.

## Cierre

- Commit/PR: consultar seguimiento Notion enlazado.
- Pendientes externos: entrega real por Zoho y Outlook clásico; el usuario confirmó que no dispone de Outlook. No bloquean revisar el código, pero no deben declararse validados. No se contrató un servicio externo de capturas.
