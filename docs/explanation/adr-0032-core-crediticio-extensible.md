# ADR 0032: responsabilidades y evolución del core crediticio

- Estado: aceptado como dirección arquitectónica; implementación incremental pendiente.
- Fecha: 2026-10-29.
- Iniciativa: [Core crediticio extensible](https://app.notion.com/p/40961db7db7f819a8af1d7c3cfec2df7).
- Complementa ADR 0006, 0012, 0020 y 0023. No sustituye sus restricciones operativas vigentes.

## Contexto

La vertical PF/MXN implementa crédito simple con cuota nivelada o capital fijo.
Los productos, revisiones, paquetes y cronogramas conservan evidencia histórica;
los pagos usan un motor decimal y registro transaccional. La ampliación prevista
incluye Bullet, dos operativas revolventes y retención/remesa patronal de Nómina.

Una modalidad financiera no determina por sí sola amortización, interés o cobranza.
Una disposición revolvente puede reutilizar la misma amortización que un simple;
dos disposiciones de una línea pueden tener métodos distintos si el contrato lo
permite. Nómina puede reutilizar esa base y añadir seguimiento de retenciones sin
convertir un descuento reportado en dinero recibido.

El contraste de auditoría identifica defectos matemáticos concretos y límites de
extensión, pero no justifica sustituir el monolito ni duplicar todo el motor.

## Decisión

1. Mantener el monolito modular Laravel y separar cálculo puro de autorización,
   bloqueos, transacciones, persistencia y auditoría.
2. Componer responsabilidades independientes: estructura financiera, amortización,
   cálculo/devengo, exigibilidad, clasificación comercial, cobranza/recepción,
   conciliación y aplicación. No crear un motor por cada combinación.
3. Introducir contratos tipados en límites útiles. Las estrategias reciben
   condiciones efectivas y producen resultados verificables; no consultan
   preferencias mutables del actor ni proveedores externos.
4. Congelar condiciones efectivas y versión del motor. La versión del algoritmo es
   distinta del formato de serialización. Los adaptadores históricos no editan
   snapshots; una corrección no reinterpreta silenciosamente contratos firmados.
5. El backend declara las capacidades y combinaciones permitidas del producto;
   Vue recibe opciones, etiquetas y ayuda. Amortización común no significa que
   cualquier combinación de producto, tasa y calendario sea válida.
6. Modelar devengado, exigible, vencido, pagado y proyectado de manera diferenciada.
   Capital intermedio cero no impide intereses exigibles. Acumular intereses no
   autoriza capitalizarlos; Flat es una base de cálculo sobre capital original.
7. Separar recepción, conciliación y aplicación financiera. Una remesa puede
   distribuirse entre créditos y una retención puede conciliarse con varias
   remesas. Toda aplicación debe conservar origen y evitar doble efecto.
8. Mantener proyecciones operativas reconstruibles, con fecha/posición de origen,
   sin convertirlas en otra fuente contractual. Distinguir avance por tiempo,
   registro de devengos, corte de facturación y corte mensual de cartera.
9. Mantener permisos por equipo y responsabilidad por sucursal. Una instalación
   representa una financiera; los equipos no son tenants de los créditos.
10. Conservar la página/editor de Productos. Cobranza se incorpora progresivamente
    y la política de originación conserva su propio versionado. Convenios
    simultáneos no se implementan como versiones temporales alternativas.

## Consecuencias

- Bullet se implementa sobre simple sin depender de Revolvente. Sus dos políticas
  comparten base: intereses periódicos o al vencimiento, capital al vencimiento.
- Revolvente ofrece saldo global y por disposición en su primera entrega. En el
  segundo, cada disposición tiene condiciones/calendario propios. Un reverso debe
  revisar dependencias posteriores de la línea, incluida disponibilidad reutilizada.
- Nómina comienza manualmente. La política de reconocimiento entre retención y
  recepción se define con casos antes de habilitar operación; no se presume que
  la deuda o la mora desaparezcan ni que deban continuar sin cambios.
- Los casos independientes preceden al código dependiente. Las pruebas acompañan
  cada incremento; una fase final de validación no las reemplaza.
- No se decide un esquema definitivo de tablas, endpoints, librerías de fórmulas
  ni motor universal de sobrescrituras sin justificarlo contra flujos reales.
- Se conservan componentes correctos. Toda capacidad nueva revisa auditoría,
  documentación e invariantes históricas en su misma entrega.

## Límites y estado

Primer incremento local: motor 2 versionado para precisión Act/360 y guardas de
los métodos existentes. La referencia explica su adopción sin cambiar evidencia
histórica. Esto no acredita Bullet, Revolvente, Nómina, proyecciones ni capacidad
productiva implementados. Tasas variables/TIIE,
Flat operativo, capitalización, calendarios arbitrarios, editor de fórmulas y
proveedores bancarios siguen fuera del alcance inmediato. KYC/PLD/SIC condicionan
autorización desde aplicación, no contaminan cálculo financiero.

Los contratos y criterios verificables se mantienen en la
[referencia del core](../reference/core-crediticio.md); las fechas/prioridades en
Notion y el avance técnico en la [Agent Note](../agent-notes/core-crediticio-extensible.md).
