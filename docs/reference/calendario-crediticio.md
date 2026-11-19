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
- Solo Super Admin global publica. La última publicación se muestra como versión institucional vigente. Cada publicación conserva sus fechas, cobertura y huella.
- Ejecutar la migración y actualizar el catálogo de permisos y menú mediante
  `ModulesAndPermissionsSeeder` y `MenubarItemsSeeder` en el orden habitual.
  La navegación del menú mantiene sus activaciones configurables. El reinicio
  habitual de QA conserva las versiones como configuración institucional.
