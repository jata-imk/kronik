# Configurar y comprobar la fiscalidad de un producto

La configuración no habilita dinero. El simulador puede aplicar impuestos al
activar explícitamente la proyección fiscal; véase `qa-simulacion-fiscal.md`.
Los paquetes contractuales QA anteriores a impuestos no cambian.

## Preparación

Aplicar migraciones con el procedimiento habitual de despliegue y respaldo. La
migración `2026_09_27_100000_add_fiscalidad_to_product_versions` agrega dos columnas
JSON anulables. No requiere seeders ni variables nuevas. No ejecutar `migrate:fresh`
en la VPS: las pruebas automatizadas lo usan solo en su base aislada.

## Recorrido de QA

1. En Productos crediticios, crear o editar un **borrador** y abrir Fiscalidad.
   Deben verse los avisos de prueba y ambos intereses sin definir.
2. Seleccionar Gravado para el ordinario y guardar sin tasa/base: los errores
   deben aparecer en español en Fiscalidad, con contador y foco de error.
3. Capturar una tasa sintética `16.12345678` y seleccionar explícitamente
   Importe íntegro de este concepto. La tasa no es la tasa de interés anual.
4. Elegir Exento para el moratorio. Sus campos de tasa/base deben ocultarse y
   limpiarse. Probar también No causa impuesto y Sin definir, sin equivalencias.
5. Agregar dos comisiones en Comisiones; regresar a Fiscalidad y asignar a cada
   una tratamientos diferentes. La base comercial porcentual del crédito no debe
   convertirse en base del impuesto.
6. Cambiar a Declaración institucional: sin referencia no debe guardar. Registrar
   un respaldo ficticio claramente marcado QA; esto no acredita validación fiscal.
7. Guardar, reabrir y comprobar valores. Desde la tabla, Ver fiscalidad debe abrir
   una consulta de solo lectura. En versiones antiguas sin configuración debe
   aparecer una advertencia de ausencia, no de exención.
8. Activar y duplicar. La nueva versión debe conservar la configuración inicial;
   editarla no debe modificar la consulta de la anterior ni paquetes existentes.
9. Repetir consulta en móvil: drawer desplazable, etiquetas legibles y sin
   desbordamiento horizontal. Comprobar acceso según permisos de productos.

Si la institución necesita una base distinta del importe íntegro del concepto,
dejarla pendiente y solicitar la ampliación del motor. No aproximarla usando
capital, texto libre ni una tasa arbitraria.

## Pruebas automatizadas

- Backend: `php artisan test --filter=ProductosCrediticiosTest`.
- Frontend: `npm run test:unit`.
- Navegador aislado: `npm run test:e2e -- productos-crediticios.spec.js`.
- Compilación: `npm run build`.

Ver ADR 0016 y las decisiones funcionales fiscales para límites y continuación.

## Referencias visuales

- [Consulta en escritorio](../assets/fiscalidad-producto-desktop.png).
- [Consulta en móvil](../assets/fiscalidad-producto-mobile.png).
