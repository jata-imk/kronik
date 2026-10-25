# Corrección SIC — fecha del consentimiento

## Referencias

- Notion: [manual visual QA](https://app.notion.com/p/3fe61db7db7f812d878ccccc1e90d651).
- Rama: `fix/sic-consentimiento-zona-horaria`, base `main` `06a5723`.
- PR: [#42](https://github.com/jata-imk/kronik/pull/42), implementación `16d96e6`.
- ADR relacionados: [0022, SIC QA](../explanation/adr-0022-sic-qa-evidencia.md).

## Objetivo

Resolver SIC-TZ-001, encontrado al ejecutar el manual en QA. El usuario autorizó
corregir este bloqueo y continuar; los otros hallazgos quedan registrados.

## Estado actual

- En progreso; reanudado por el usuario el 2026-10-24.
- Reproducido: ISO `2026-10-20T06:09:48.000Z` se relee como `12:09:48Z` con
  `APP_TIMEZONE=America/Mexico_City`, bloqueando la vigencia del consentimiento.
- El servicio convierte el instante a la zona de almacenamiento antes de escribir.
  No cambia reloj, políticas SIC, permisos, vencimiento ni evidencia histórica.
- PR #42 integrada en main `d218a0c`, CI aprobada. José confirmó el despliegue
  manual el24/10/2026; no informó el commit efectivo del servidor.
- Comportamiento validado en QA: consentimiento4 capturado24/10 23:51 México,
  guardado25/10 05:51:06Z, frente a servidor05:51:34GMT. Permitió preparar y enviar
  de inmediato consulta3. El consentimiento2 se revocó y conserva evidencia.

## Evidencia

- Regresión HTTP con persistencia y nueva lectura: UTC y America/Mexico_City,
  entradas ISO con `Z` y `-06:00`, hash inmediatamente utilizable y revocación.
- Fecha futura sigue rechazada, con mensaje español y sin crear otro consentimiento.
- Regresión completa local en SQLite `:memory:`: **420 aprobadas, una omitida,
  40436 aserciones**; `php artisan test --compact` en el worktree, sin acceso a QA.
- Frontend: **90 pruebas aprobadas** (32 archivos); `npm run test:unit`.
- `npm run build` aprobado; avisos de recursos estáticos, Browserslist y tamaño
  de chunks preexistentes. Pint sobre los tres archivos PHP modificados aprobado.
- El primer intento sin `.env`/build aislados falló por preparación local; se creó
  `.env` de pruebas sin credenciales reales y se compiló antes de la suite final.
- CI MariaDB del commit `16d96e6`: aprobada el 24/10/2026. Hook pre-push:
  420 pruebas backend aprobadas, una omitida, 40436 aserciones y build aprobado.
- Navegador aislado, 24/10/2026: `sic-qa.spec.js` aprobado, tres servicios con
  fixtures y zona America/Mexico_City; sin llamadas externas. `sic-safety.spec.js`
  falla buscando el texto histórico «Histórico heredado»; se registra por separado,
  sin modificar ese test o la aplicación dentro de esta corrección.
- QA: bloqueo original en cliente sintético8, consentimiento2 y SOL-6 conservado.
  Validación posterior positiva para la fecha con consentimiento4; la consulta3
  terminó HTTP400 por domicilio, otro problema registrado como SIC-DOM-001.
- Sin migraciones, seeders ni variables nuevas. No reparar consentimientos previos
  automáticamente: conservar evidencia y revocar/registrar uno nuevo por la interfaz.

## Siguiente paso

Corrección de fecha entregada y validada. Continuar con
[domicilio sandbox](sic-domicilio-sandbox.md), que es el nuevo bloqueo.
Worktree: `output/manual-credito-simple/worktrees/sic-fecha/`; `vendor` es copia
propia y `node_modules` es un enlace al workspace.

## Cierre

- Commit/merge: implementación16d96e6, PR42, merge d218a0c.
- Diferidos: los demás hallazgos del manual; SIC productivo permanece cerrado.
