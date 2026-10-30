# Documentación por temas

Cada ficha explica brevemente un tema y reúne su documentación Diátaxis,
decisiones y contexto de seguimiento. Un documento puede aparecer en varios
temas cuando se comparte entre módulos.

## Operación crediticia

| Tema | Qué encontrarás |
| --- | --- |
| [Core crediticio y extensibilidad](core-crediticio.md) | Responsabilidades financieras, compatibilidad histórica y evolución hacia Bullet, Revolvente y Nómina |
| [Crédito simple y revisión del producto](credito-simple.md) | Recorrido completo QA, alcance y preparación de pruebas |
| [Empresa y sucursales](empresa-sucursales.md) | Configuración institucional, sucursales y contextos operativos |
| [Usuarios, equipos y permisos](usuarios-equipos-permisos.md) | Acceso, superadministrador, roles y membresías |
| [Clientes, expediente e identidad fiscal](clientes-expediente.md) | KYC, documentos, relaciones y validación RFC |
| [Productos y simulación](productos-simulacion.md) | Versiones, comisiones, calendarios, impuestos y CAT |
| [Originación y aprobación](originacion-solicitudes.md) | Solicitudes, revisiones, dictámenes y aprobación |
| [SIC y Círculo de Crédito](sic-circulo-credito.md) | Consultas, evidencia, historial y reportes QA |
| [Documentos, contratos y firmas](documentos-contratos-firmas.md) | Plantillas, PDF, paquetes y firma digitalizada |
| [Desembolsos, pagos y anticipos](cartera-pagos.md) | Crédito, devengo, pagos, liquidación y reversos |
| [Fiscalidad y cumplimiento](fiscalidad-cumplimiento.md) | Tratamientos por concepto, perfiles y decisiones pendientes de validación profesional |
| [Auditoría, reportes y contabilidad](auditoria-reportes.md) | Actividad, trazabilidad y contexto del backlog de reportes |

## Aplicación y mantenimiento

| Tema | Qué encontrarás |
| --- | --- |
| [Correos de acceso](correos-acceso.md) | Invitaciones, recuperación, verificación, SMTP y muestras |
| [Diseño, navegación y portada](diseno-navegacion.md) | Identidad visual, menú contextual y presentación de módulos |
| [Despliegue y mantenimiento QA](despliegue-mantenimiento.md) | VPS, configuración, catálogos, reset y archivos |
| [Calidad y documentación viva](calidad-documentacion.md) | Pruebas, CI, Diátaxis, ADR, Agent Notes e índices |

## Buscar por tipo de documento

- [Tutoriales](../tutorials/index.md): aprendizaje; aún sin tutoriales completos.
- [How-to](../how-to/index.md): procedimientos concretos.
- [Referencia](../reference/index.md): reglas y configuración exactas.
- [Explicación y ADR](../explanation/index.md): decisiones y contexto.
- [Agent Notes](../agent-notes/index.md): ejecución y evidencia por entrega.
- [Fuentes](../fuentes/index.md): lecturas externas de apoyo.
- [Backlogs locales](../todos/index.md): contexto; Notion conserva prioridades.

## Mantener este índice

Al crear o modificar documentación, actualizar su índice de sección y la ficha
del tema correspondiente. Crear `docs/topic/<tema>.md` si el tema no existe y
registrarlo aquí. Conservar en las fichas enlaces descriptivos y resúmenes
breves; el contenido y el estado viven en sus documentos canónicos.

Consulta la [regla de documentación viva](../reference/documentacion-viva.md)
y el [índice general](../index.md).
