# ADR 0006: Versionado de productos y motor de cálculo

- Estado: aceptado
- Fecha: 2026-08-12
- Nota: la exclusión original de comisiones financiadas fue sustituida por ADR 0007.

## Contexto

Un producto conserva identidad comercial, pero sus tasas, montos, plazos, comisiones y reglas cambian. Solicitudes y créditos deben seguir explicándose con las condiciones originales. El simulador necesita aplicar las mismas convenciones sin acoplarlas a la pantalla. La instalación representa una financiera y las sucursales no son dueñas del catálogo.

## Decisión

- `productos_crediticios` guarda la identidad global por instalación; `producto_versiones` y sus entidades hijas guardan condiciones y reglas.
- Solo un borrador sin usos puede editar condiciones. Activar genera snapshot y hash; una versión activada, programada o utilizada no permite cambios financieros ni eliminación.
- Una solicitud o crédito futuro debe invocar `ProductoVersionService::registrarUso()` y conservar su snapshot.
- La activación puede ser inmediata o programada. Al entrar en vigor, la versión activa anterior se retira sin borrarse.
- Montos, tasas y resultados usan decimales. Las tasas son porcentaje anual; V1 usa días reales/360 y redondeo half-up.
- Periodicidades y comisiones son filas normalizadas. Las reglas viven separadas de parámetros comerciales.
- CAT es un servicio separado que resuelve la ecuación de valor presente de la Circular 21/2009 y se identifica como informativo.

## Consecuencias

- Cambiar condiciones exige duplicar una versión y activarla con vigencia explícita.
- Originación debe registrar uso y snapshot antes de aceptar una solicitud.
- Crédito revolvente puede agregar reglas y motor propios sin introducir funcionalidad V2 ahora.
- V1 no agrega IVA ni ajusta días inhábiles. El tratamiento de comisiones iniciales se define en ADR 0007.

## Fuentes

- Banxico, Circular 21/2009: https://www.banxico.org.mx/marco-normativo/normativa-emitida-por-el-banco-de-mexico/circular-21-2009/%7B29285862-EDE0-567A-BAFB-D261406641A3%7D.pdf
- Banxico, explicación del CAT: https://www.banxico.org.mx/sistema-financiero/d/%7B5BD610E5-EE24-04AA-A21E-53B2176C2228%7D.pdf
- CONDUSEF, RECO: https://registros.condusef.gob.mx/reco/glosario.php
