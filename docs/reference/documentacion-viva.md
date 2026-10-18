# Documentación viva e índices por tema

## Fuentes de verdad

| Información | Ubicación |
| --- | --- |
| Alcance, orden y prioridades de producto | Notion del proyecto |
| Comportamiento implementado | Código y pruebas del commit correspondiente |
| Uso, operación y referencia vigentes | Documentos Diátaxis de `docs/` |
| Decisiones arquitectónicas y sus motivos | ADR en `docs/explanation/` |
| Estado de ejecución, evidencia y siguiente paso | Agent Note del backlog o tarea transversal |
| Cómo encontrar todo lo relacionado con un tema | `docs/topic/<tema>.md` |

Los documentos locales de roadmap/backlog son contexto de apoyo. No sustituyen el orden aprobado en Notion. Un índice enlaza documentos; el comportamiento se mantiene en su documento canónico para evitar versiones divergentes.

## Diátaxis y navegación

- `docs/tutorials/`: aprendizaje guiado. Actualmente contiene el índice, sin tutoriales completos.
- `docs/how-to/`: instrucciones para realizar una tarea.
- `docs/reference/`: reglas, contratos, configuración y comandos exactos.
- `docs/explanation/`: contexto y decisiones, incluidos ADR.

Los índices de estas cuatro secciones enumeran sus documentos. El [índice por temas](../topic/index.md) permite encontrarlos por problema o módulo, por ejemplo SIC, documentos, correos o pagos. Las Agent Notes, fuentes y backlogs se enlazan como contexto adicional, identificados con su función.

Cada `docs/topic/<tema>.md` contiene:

1. Un título y una explicación breve del tema y sus límites.
2. Enlaces descriptivos agrupados según el material disponible: tutoriales, operación, referencia y decisiones.
3. Enlaces de seguimiento, fuentes o temas relacionados cuando aporten contexto.

No hace falta crear secciones vacías ni copiar pasos de las guías. Un documento compartido puede enlazarse desde varios temas. Las fichas no deben repetir estados o conteos de pruebas que ya se mantienen en una nota o PR.

## Cuándo actualizar

| Evento | Revisión necesaria |
| --- | --- |
| Cambio funcional o técnico | Guías y referencias afectadas, permisos/configuración y decisiones si corresponden |
| Documento nuevo, movido o renombrado | Índice de sección, ficha temática, índice temático si es un tema nuevo y enlaces entrantes |
| Cambio de rama, PR o traspaso de trabajo | Agent Note y evidencia enlazada |
| Integración y despliegue | Estado de la entrega y siguiente paso en notas, Notion y resúmenes afectados |
| Resultado posterior de QA o confirmación del usuario | Evidencia fechada y pendientes reales; retirar el estado pendiente del caso confirmado |
| Decisión arquitectónica modificada | Nuevo ADR o actualización de estado con enlace al ADR que lo sustituye; conservar la justificación histórica |

No se requiere autorización adicional para registrar resultados que el usuario ya confirmó. No se espera al final de una sesión larga para conservar un hallazgo que afecta cómo continuar. Al retomar un tema se contrasta su estado con código/PR, Notion y última evidencia disponible; una fecha reciente por sí sola no demuestra vigencia.

## Estado y evidencia

Describir por separado cuando aplique:

- **Implementado:** hay código y pruebas identificables.
- **Integrado:** el cambio pertenece a la rama de destino; enlazar PR o commit de integración.
- **Desplegado:** está instalado en el entorno indicado.
- **Validado:** se comprobó un caso concreto, por pruebas automatizadas, inspección manual o confirmación del usuario, identificando el origen de la evidencia.

Una entrega puede tener código integrado y mantener un escenario pendiente de QA. Las pruebas locales no demuestran entrega SMTP ni certifican otros clientes de correo. Registrar únicamente los escenarios y versiones informados. Los conteos de pruebas pertenecen al commit que los produjo, no a una cifra global que deba copiarse en todos los documentos.

Las Agent Notes se condensan al entregar: referencias, estado final, evidencia, limitaciones y siguiente acción concreta. Las instrucciones permanentes deben vivir en Diátaxis. No incluir contraseñas, tokens utilizables, respuestas SIC ni información personal innecesaria.

## Comprobaciones antes de cerrar

- El comportamiento descrito coincide con la entrega y sus límites.
- Cada documento Diátaxis tiene enlace en su índice de sección y en al menos una ficha temática; las fichas están en `docs/topic/index.md`.
- Los enlaces locales resuelven y los títulos permiten distinguir documentos del mismo tema.
- Notion, Agent Notes y resúmenes afectados reflejan integración, despliegue y resultados de QA conocidos.
- No se mantienen pendientes ya confirmados ni se presenta una validación parcial como certificación general.
- La PR incluye documentación revisada y evidencia de las comprobaciones. Los cambios exclusivamente documentales requieren revisión de enlaces y contenido; no necesitan ejecutar pruebas funcionales nuevas.

El hook local y CI exigen algún cambio documental cuando cambia código. **No verifican que se haya actualizado el documento correcto, que los enlaces funcionen ni que el contenido esté vigente.** Esa revisión forma parte de la entrega, aunque el control automático esté en verde.

## Relacionado

- [Reglas del repositorio](../../AGENTS.md).
- [Índice general](../index.md) e [índice temático](../topic/index.md).
- [Automatización de documentación](automatizacion-documentacion.md).
- [Flujo de tareas, pruebas y PR](flujo-tareas-pruebas-pr.md).
- [Definición de terminado](definicion-terminado.md).
- [Plantilla de Agent Notes](../agent-notes/template.md).
