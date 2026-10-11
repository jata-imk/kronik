# ADR 0022: SIC sandbox con evidencia cifrada

Estado: aceptada para QA. Fecha: 10/10/2026.

## Contexto

P1 cerró SDK heredados que desactivaban TLS, usaban personas demostrativas y
carecían de consentimiento/procedencia verificables. Los registros se preservaron.
El usuario autorizó recuperar los tres servicios existentes con credenciales QA.

## Decisión

Usar un gateway propio sobre HTTP Laravel. Exigir flag QA, sandbox, host autorizado
y credenciales. TLS verifica confianza del sistema o un CA bundle explícito;
rechazar redirecciones y no reintentar POST. Mantener cerrados los SDK antiguos.

Preparar bajo bloqueo del cliente, capturar consentimiento, identidad y revisión
vigente, despachar tras commit. El worker revalida permisos/requisitos con el
equipo y sucursal originales, sin modificar el usuario persistido. Idempotencia
impide duplicar el envío. Un intento abierto bloquea otra consulta del mismo
cliente/servicio. Un envío incierto necesita conciliación explícita; un folio POST
no se usa como supuesto endpoint de recuperación.

Separar metadatos de petición/respuesta/resultado cifrados e inmutables. Verificar
hashes al leer; auditar lectura y conciliación sin payloads en logs. Migrar
históricos de manera reanudable, verificando contenido antes de retirar texto
plano y conservando IDs y un respaldo recuperable.

Acreditar SIC únicamente para revisión actual, consentimiento vigente e identidad
intacta. La política versionada indica APIs admitidas y si ausencia de score
bloquea o permite evaluación manual. E2 bloquea; no hay aprobación por score ni
caducidad QA inventada. La aprobación conserva la evidencia específica.

Normalizar una base común y secciones opcionales, conservar el original. Mostrar
score/razones sin interpretación de riesgo automática. Separar monedas, distinguir
ausencias de cero y advertir las fechas propias de cada cuenta.

## Consecuencias

Se recupera QA sin reabrir SDK inseguros ni atribuir procedencia al histórico.
La firma P384/SHA256 hex(DER) queda aislada y probada. Transporte productivo
cerrado hasta validación del proveedor, contratos, credenciales y políticas.
Pruebas automáticas con fakes; prueba explícita sandbox solo con casos sintéticos
oficiales, sin insertar evidencia de aprobación.
