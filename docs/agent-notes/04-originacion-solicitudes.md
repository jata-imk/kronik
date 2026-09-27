# Backlog 04 - Originación y solicitudes

## Referencias

- Notion: https://app.notion.com/p/3a061db7db7f81948d4ae8fa9118ac1d
- Coordinación: https://app.notion.com/p/3e161db7db7f817a8c89ec30767d4666
- Rama actual: docs/decisiones-fiscales-contratos-mora (base main 67cb6ee).
- PR: [P0 #17](https://github.com/jata-imk/kronik/pull/17), [P1 #16](https://github.com/jata-imk/kronik/pull/16), [P2 #18](https://github.com/jata-imk/kronik/pull/18).
- P3 parcial: [PR #19](https://github.com/jata-imk/kronik/pull/19), base #18, borrador.
- P3 aprobación: [PR #20](https://github.com/jata-imk/kronik/pull/20), base #19, borrador.
- ADR relacionados: 0010–0014, 0005–0009

## Objetivo

Ejecutar los incrementos aprobados del recorrido de solicitud a primer pago.
El alcance completo y las decisiones de producto permanecen en Notion.

## Estado actual

- Estado: en progreso
- Última actualización: 2026-09-27
- Último punto estable: main 67cb6ee, sincronizado; PR #16–#21 integrados.
- P0: Notion ampliado y ADR registrados (6359c99).
- P1: SIC saneado/navegación (c29af31), rama fix/sic-navegacion-segura.
- P2: borrador/revisión/asignación implementados y verificados (`c4b5770` más seguimiento); PR #18 borrador.
- P3 parcial: devolución/reenvío, cierres y dictámenes humanos por revisión;
  bandejas de Evaluación y Cumplimiento reutilizan el detalle de solicitud.
- P3 siguiente incremento: políticas versionadas, requisitos y aprobación individual/dual;
  huellas de identidad/documentos, vigencia y evidencia. Habilitación por defecto cerrada.
- P4–P9 pendientes. Sin operación monetaria habilitada.
- Se preserva `.playwright-mcp/` local ajeno.

## Decisiones pendientes

- Cerradas funcionalmente: fiscalidad configurable por concepto, responsabilidad
  contractual institucional y gracia A por defecto/B configurable. Especificación
  en Notion y `docs/reference/decisiones-fiscales-contractuales-mora.md`.
- Continuación aprobada: coexistencia configurable por producto/contrato,
  pagos pasados admitidos y excedentes fuera del sistema inicialmente.
- Pendientes: precisar limitación del ordinario/interacción con gracia y pagos
  pasados con movimientos posteriores o periodos cerrados; otros detalles de cálculo
  no especificados permanecen pendientes. No inferir desembolsos retroactivos.
- Habilitación real sigue exigiendo configuración fiscal y contratos validados
  por cada institución, además de las puertas financieras y de cumplimiento vigentes.

## Evidencia

- P2 final: 181 pruebas / 1092 aserciones, CI 35498852504 aprobado.
- P3 parcial: 188 backend / 1240 aserciones / 1 omitida; 56 frontend; build y Pint aprobados.
- Continuación aprobación: 196 backend / 1341 aserciones / 1 omitida; 60 frontend;
  build y Pint aprobados. Regresión E2E de 9 aprobada, incluyendo política y bloqueos.
- E2E ampliado con dictámenes, devolución, revisión 2 y rechazo: 9 aprobados.
- QA encontró y corrigió etiquetas accesibles, sincronización del plazo y fallo
  de orientación en Select de PrimeVue 4.3.1; guard temporal documentado y probado.
- Se conserva regresión de fecha empresarial y separación de permisos reservados.
- GitHub reportó alertas de dependencias en main al publicar; no se remediaron en estos incrementos.
- Revisión manual: fuentes Notion y arquitectura existente inspeccionadas.
- Migraciones o seeders: 2026_09_20_000000_create_solicitudes_tables; ModulesAndPermissionsSeeder
  agrega cuatro permisos, sin asignarlos a roles. Solo se ejecutó en BD de pruebas aisladas.
- Continuación: 000300 agrega políticas/evidencia/vigencia; permisos `approve solicitudes`
  y `manage origination productos-crediticios`. `.env` real no modificado; bandera false por defecto.
- Correcciones de QA: 197 backend / 1385 aserciones / 1 omitida; 65 frontend;
  build, Pint y diff-check aprobados. 9 E2E de regresión aprobados y 1 escenario
  dual separado aprobado con capturista/aprobador normales y notas reservadas ocultas.
  Capturas de captura, política, detalle escritorio/móvil y aprobación inspeccionadas.
  Ejecutar backend y E2E secuencialmente: una ejecución simultánea tuvo fallos
  transitorios de render/login; repetición aislada completa pasó sin cambios de aplicación.
  Sin migraciones nuevas ni cambios en la VPS. Seeder dual exclusivo del runner E2E.

## Siguiente paso

Continuar P4 formalización desde main tras la corrección de accesos de Clientes,
revalidando vigencia/evidencia antes de contrato/anexo y firma. P5 desembolso y P6
primer pago completarán después la primera vertical. No avanzar SIC real ni dinero
sin políticas validadas. VPS exclusivamente QA; no modificar la solicitud manual.

QA P3 corregido en 7933920: tarjetas/contexto, importes MXN, accesos desde cliente,
búsqueda de responsables, dictámenes resumidos, pendientes explicados y recarga
con confirmación. CI aprobado (35550769722, MariaDB/build/backend/frontend).
Usuario autoriza integrar esta ronda; #17, #16, #18 y #19 ya están en main.
#20 queda como último PR de integración; verificar su estado remoto al retomar.

### Continuación 2026-09-26

- La integración anterior ya terminó; las referencias a PR borrador/sin merge
  de los párrafos históricos anteriores quedan superadas por main `9a563f7`.
- Petición nueva: no rediseñar pantallas de P2/P3; usar Documentos y Productos
  como referencia visual en los siguientes incrementos, con orientación contextual.
- Corrección previa a P4: quitar ambos botones del encabezado de Clientes y
  ubicar Expediente y Nueva solicitud en el menubar; conservar rótulos existentes,
  precargar el cliente y verificar sucursal también al abrir la URL directamente.
- Migración aditiva de menús; no ejecutar el seeder general sobre QA para aplicarla.
  Guion: `docs/how-to/accesos-contextuales-clientes.md`.
- Verificación: 199 backend / 1416 aserciones / 1 omitida, 65 frontend,
  3 E2E de Clientes aprobados; build, Pint y diff-check aprobados.
- P4 todavía no implementado: preparar primero paquete contractual trazable
  revalidando aprobación y congelando condiciones; separar la firma digitalizada
  en otro incremento. No representar un PDF generado como contrato firmado.

## Cierre

- Actualización 2026-09-27: #21 confirmado integrado en 67cb6ee. Esta entrega solo
  documenta decisiones del usuario en iniciativa/Backlogs 04 y 07 y referencia local;
  precisa ADR 0012 sin cambiar arquitectura. No hay cambios de código, BD, estados,
  versiones existentes, banderas ni VPS. P4–P6 siguen pendientes de implementación.
- Verificación de esta entrega: coherencia documental, enlaces relativos y diff-check.
  El hook de publicación pasó 199 pruebas / 1416 aserciones / 1 omitida y build;
  las pruebas de las nuevas reglas siguen pendientes de su implementación en P4–P6.
- Commit o merge: P3 parcial c8cff6f y corrección visual 2c401ad; PR apilados 17 → 16 → 18 → 19, sin merge.
- Aprobación: e573a37, PR #20 apilado sobre #19, sin merge.
- Pendientes diferidos: 02.5 sigue diferido; producción SIC en P7.
