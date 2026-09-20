# ADR 0013: cumplimiento según operador y vigencia

- Estado: aceptado
- Fecha: 2026-09-20
- Alcance: arquitectura objetivo

## Contexto

El producto atiende prestamistas profesionales y financieras mexicanas, incluidas
S.A./SAPI; no toda instalación es SOFOM ENR o SOFIPO. Los backlogs 06/06.1 tenían
supuestos de régimen incompatibles con esa audiencia.

## Decisión

Versionar perfil del operador, aplicabilidad, metodología, fuentes y fechas de
vigencia. Configuración pendiente bloquea habilitación, no equivale a exención.
Comenzar con apoyo a revisión manual: evidencia, responsable, dictamen y bloqueo;
automatización detecta señales y no sustituye decisiones humanas de cumplimiento.
Separar permiso de lectura operativa de notas sensibles. Conservar historia y
reabrir revisión ante cambios materiales. No imponer catálogo SOFIPO ni homologar
actividad económica arbitrariamente; 06.1 requiere confirmar fuente aplicable.

## Consecuencias

Asesoría especializada valida perfil, textos, listas, riesgo y periodicidad antes
de producción. No se considera cubierto el cumplimiento por tener esta arquitectura.
Referencia: [LFPIORPI](https://www.diputados.gob.mx/LeyesBiblio/pdf/LFPIORPI.pdf),
[SOFOM ENR](https://www.gob.mx/cnbv/acciones-y-programas/disposiciones-legales-sofom-enr).
El [Acuerdo 115/2026](https://sidof.segob.gob.mx/notas/docFuente/5795797)
contiene vigencias diferenciadas: general 30/11/2026; medidas específicas en 2027
y primera auditoría en 2028. No describir todo como vigente al 20/09/2026.
