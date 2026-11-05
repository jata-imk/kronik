# Consulta, edición e históricos

## Referencias y estado

[Tarea de Notion](https://app.notion.com/p/3fe61db7db7f81138be2df5cfa15564c).
Base funcional `33c79c4`, con PR #66 integrada. Inventario contrastado mediante
SELECT de los 19 módulos de la BD local; sin escrituras operativas. PR #65 de
Bullet ajena. [Contrato, matriz y módulos](../reference/consulta-edicion-historicos.md).

Implementación integrada en `main` el **2026-11-04** mediante PR #67–#71,
en ese orden y sin conflictos. Merge final
[`4b983c3`](https://github.com/jata-imk/kronik/commit/4b983c32828c287dd971da254c8c19fdb0dcd51c).
El árbol completo de ese merge coincide exactamente con el envío validado
`0ec1824`; cada integración previa conserva el árbol de su incremento revisado.
Las PR posteriores se cambiaron a base `main` antes de fusionarlas; la tabla
conserva sus bases de revisión originales.

Código funcional revisado `5da61d0`; sin migraciones, seeders, variables ni permisos
nuevos. **Pendientes despliegue y QA del usuario en la VPS QA**; el usuario realizará
el despliegue desde `main`. Evidencia de Windows/Chromium, 2026-11-02–03,
America/Mexico_City; datos sintéticos y SQLite aislado para E2E.

| PR | Rama | Base de revisión | Código revisado |
| --- | --- | --- | --- |
| [#67 Productos](https://github.com/jata-imk/kronik/pull/67) | feat/consulta-productos | main | bc6e533, a1fff7f |
| [#68 Plantillas](https://github.com/jata-imk/kronik/pull/68) | feat/consulta-plantillas | #67 | bab3553 |
| [#69 Originación](https://github.com/jata-imk/kronik/pull/69) | feat/originacion-guiada | #68 | 1979abf |
| [#70 Usuarios/Sucursales](https://github.com/jata-imk/kronik/pull/70) | feat/consulta-administracion | #69 | 37b9c71 |
| [#71 Empresa/Menú y cierre conjunto](https://github.com/jata-imk/kronik/pull/71) | feat/consulta-empresa-menu | #70 | 5da61d0 |

## Resultado

Mismos formularios para lectura y captura, con ID exacto fuera de página,
inmutabilidad en backend y capacidades por estado/permiso. Plantillas activas o
retiradas consultan contenido completo y PDF existente. Política visible con su
número independiente e historia; retirada impide políticas nuevas. Captura/envío
sin política conservados. Empresa GET no crea singleton. Menú agrega auditoría
transaccional de gestión sin valores sensibles. Consultar estos formularios no
requiere eventos nuevos; visores y eventos auditables existentes se conservan.

La última PR incluye correcciones del barrido conjunto: búsqueda de variables
permitida; recarga solo auth revoca edición/creación/lectura; cerrar consulta y
retirar enseguida no reabre el formulario por una respuesta con modo anterior.
Spatie, recursos globales y filtros/paginación de PR #66 conservados.

## Validación y revisión

- CI de los cinco commits de PR aprobada antes de integrar; incluido
  [CI del conjunto 0ec1824](https://github.com/jata-imk/kronik/actions/runs/41588660429),
  con concurrencia de folios en MariaDB. Integración verificada mediante
  `git diff --exit-code 0ec1824 origin/main` sin diferencias antes de esta
  actualización exclusivamente documental. No se repiten E2E sin cambios funcionales.
- Suite backend completa: **639 aprobadas, una omitida, 42 680 aserciones**;
  pre-push de esta entrega. El PHP no cambia en las correcciones finales de Vue.
- Frontend completo: **140 aprobadas en 54 archivos** sobre 5da61d0.
- Pint: 23 archivos PHP aprobados. Build aprobado con avisos preexistentes de
  fuentes/chunks. Enlaces locales de los 17 documentos cambiados sin destinos faltantes.
- E2E de entrega: **nueve escenarios distintos aprobados por tandas finales**:
  Productos/Plantillas/política 6/6 (puerto8108), Usuarios/Sucursales 1/1
  (8107), Empresa/Menú 2/2 (8015). PDF multipágina/editor real 1/1 aislado.
  Aprobación dual/paquete QA 1/1 (8109), sin Super Admin aprobador.
  Total: **11 escenarios distintos aprobados por tandas**; no se afirma una tanda única verde.
  Intentos previos: PDF503 intermitente, prueba que cerraba con Escape, selección
  de módulo vacía (ahora checkbox explícito) y carrera cierre/retirada corregidos.
  Chromium/Windows produjo ERR_NO_BUFFER_SPACE al cargar JS incluso en login;
  traza/consola revisadas y Usuarios pasó al repetirlo aislado en puerto8107.
- Una tanda solapada accidentalmente sobre la misma BD E2E fue descartada; se
  repite secuencialmente. No se acredita esa ejecución como validación.
- `/review medium` por incremento y barrido acumulado cerrados sin hallazgos
  pendientes. La CLI agotó cuota durante el barrido; el usuario autorizó revisor
  delegado medium. Cierre estático final sobre **5da61d0**, última PR contra
  **37b9c71** y conjunto contra **33c79c4**. Las pruebas las ejecutó el agente principal. Ajuste final E2E de checkboxes
  de menú también revisado medium sin hallazgos; no cambia producción.

Hallazgos resueltos: controles de alta de periodicidad en consulta, referencia de
simulación, pérdida de filtros, reapertura por Escape/X, recarga del mismo usuario,
orden nulo del menú, colonia anterior, permisos obsoletos y carrera cierre→retirada.
No se modifica dominio financiero, aprobación, históricos ni prioridades de Notion.

## Capturas sintéticas

[Producto escritorio](../assets/consulta-edicion-historicos/producto-escritorio.png) ·
[Producto móvil](../assets/consulta-edicion-historicos/producto-movil.png) ·
[Plantilla móvil](../assets/consulta-edicion-historicos/plantilla-movil.png) ·
[Política móvil](../assets/consulta-edicion-historicos/politica-movil.png) ·
[Usuario móvil](../assets/consulta-edicion-historicos/usuario-movil.png) ·
[Menú móvil](../assets/consulta-edicion-historicos/menu-movil.png).

## Traspaso

La integración está confirmada. Para la VPS QA, actualizar desde `main` y seguir
el [procedimiento de despliegues posteriores](../how-to/desplegar-vps-nuevo.md#12-despliegues-posteriores),
incluida la compilación de assets. Esta entrega no exige migraciones ni seeders
nuevos. Ejecutar la [validación de consulta](../how-to/validar-consulta-solo-lectura.md)
y registrar después el commit desplegado y los resultados reales de QA. La tarea
permanece abierta hasta registrar esas etapas.
Históricos ampliados de Solicitudes/Créditos y dictámenes paginados quedan como
continuación independiente; no implementados en esta entrega. No reabre PR #15.
