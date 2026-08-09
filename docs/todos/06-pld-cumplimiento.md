# TODO 06: PLD y Cumplimiento

## Objetivo

Soportar procesos de prevención de lavado de dinero para una SOFOM ENR, sin codificar reglas legales como sustituto de asesoría especializada.

## Capacidades

- Expediente de identificación.
- Perfil transaccional esperado.
- Nivel de riesgo del cliente.
- Listas y coincidencias.
- Alertas y revisión.
- Bitácora de cumplimiento.
- Evidencia documental.
- Reportes internos.

## TODO

- [ ] Diseñar cuestionario PLD configurable por financiera.
- [ ] Agregar clasificación de riesgo: bajo, medio, alto.
- [ ] Registrar origen de recursos y destino del crédito.
- [ ] Crear modelo de alertas PLD con estado y responsable.
- [ ] Registrar revisión periódica de clientes.
- [ ] Definir integración futura con listas.
- [ ] Crear reporte interno de alertas y clientes de alto riesgo.
- [ ] Agregar permisos separados para oficial de cumplimiento.
- [ ] Implementar catalogo regulatorio de actividad economica con clave UIF/CNBV de 7 digitos y detalle separado: documentar fuente oficial y version, importar datos a BD, exponer API y sustituir el texto libre por selector estricto. SCIAN puede conservarse como referencia complementaria, no como clave regulatoria sustituta.
