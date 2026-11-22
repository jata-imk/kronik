# Calendario crediticio

La página **Configuración → Calendario crediticio** muestra la cobertura
revisada, fechas civiles, excepciones y versiones de la financiera. Un día natural
es cualquier fecha civil. Un día hábil cumple la semana configurada y las reglas
para esa fecha: la excepción puntual prevalece sobre el feriado y el descanso
semanal. La [plantilla CNBV 2026](https://sidof.segob.gob.mx/notas/5775684)
es una fuente anual editable en borrador. Cada instalación revisa su aplicabilidad.

## Vigencia y acceso

- `read calendar productos-crediticios` habilita consulta y ejemplos.
- `manage calendar productos-crediticios` habilita guardar borradores. Asignar
  ambos permisos a quienes deban preparar calendarios desde la página.
- Solo Super Admin global publica. La última publicación se aplica de inmediato
  a nuevos envíos. Cada publicación conserva sus fechas, cobertura y huella.
- Una revisión desactualizada exige abrir y confirmar de nuevo la publicación.
  Los ejemplos se calculan con la revisión que se muestra en pantalla.
- La consulta de borradores está reservada a quienes pueden prepararlos. La
  previa aplica esa visibilidad antes de validar el escenario.
- Los permisos de consulta y preparación se evalúan en el equipo activo.
  Guardar o publicar requiere autorización antes de consultar el identificador.
  El acceso a la configuración del menú contextual requiere permiso de
  lectura de menús o Super Admin global. Los enlaces contextuales autorizados
  siguen visibles para usuarios que consultan el calendario.
- Al cerrar un editor con cambios sin guardar se puede cancelar el descarte
  para conservar la captura.
- El editor y la previa reciben fechas con formato `AAAA-MM-DD`. Si la fecha
  es inválida, el servidor muestra el error y conserva la captura para corregirla.
  Al quitar una fila, los errores pendientes siguen asociados a sus fechas.
  Cargar la plantilla limpia los mensajes de la configuración reemplazada.
  El editor señala las fechas repetidas dentro de cada grupo y lleva el foco a
  un campo visible o al resumen de errores.
- Ejecutar la migración y actualizar el catálogo de permisos y menú mediante
  `ModulesAndPermissionsSeeder` y `MenubarItemsSeeder` en el orden habitual.
  La navegación del menú mantiene sus activaciones configurables. El reinicio
  habitual de QA conserva las versiones como configuración institucional.

## Efecto contractual

**Conservar la fecha** es la política heredada. **Siguiente día hábil** es
optativa en motor 2, Act/360, para las frecuencias semanales, quincenales y
mensuales, en cuota nivelada, capital fijo y ambas políticas Bullet.

El motor obtiene las fechas nominales antes de mover cada cierre, conserva las
anclas siguientes y calcula el periodo con días reales entre fechas ajustadas.
El diagnóstico del simulador identifica tanto el ancla nominal como el cierre
de interés ordinario que usa el cálculo.
Valida cobertura para las fechas consultadas y exige vencimientos crecientes.
El atraso comienza al día civil siguiente si queda una obligación impaga.
La gracia conserva sus días naturales y la política de mora del producto.

La revisión de solicitud guarda las fechas nominales, resultantes y la versión
del calendario. Paquete, formalización, desembolso, pagos y proyecciones usan
la evidencia congelada. Los nombres y motivos de las fechas se consultan en la
versión del catálogo con su permiso propio. La previa usa datos sintéticos y
calcula en memoria.

Para CAT se conservan los intervalos uniformes de pagos periódicos. En nuevas
simulaciones con el motor Act/360, un único pago al vencimiento usa días
efectivos entre 360 con cualquiera de las políticas de calendario, según el numeral 5.1 f de la
[Circular 21/2009 de Banxico](https://www.banxico.org.mx/marco-normativo/normativa-emitida-por-el-banco-de-mexico/circular-21-2009/costo-anual-total-disposicion.html).
Al generar la tabla fiscal, el paquete usa directamente el CAT informado en la
revisión original.
La periodicidad diaria mantiene su propio alcance.

- [Decisión de arquitectura](../explanation/adr-0048-calendario-crediticio.md)
- [Recorrido QA](../how-to/qa-calendario-crediticio.md)
