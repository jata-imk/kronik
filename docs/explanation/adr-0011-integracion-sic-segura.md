# ADR 0011: integración SIC verificable y segura

- Estado: aceptado
- Fecha: 2026-09-20
- Alcance: arquitectura objetivo; saneamiento antes de habilitación

## Contexto

Los tres adaptadores heredados usan SDK de sandbox, desactivan TLS y sustituyen
requests ausentes por personas de demostración. El controlador omite dependencias
del repository. Históricos sin procedencia comprobable no son evidencia productiva.

## Decisión

Cerrar el punto de ejecución heredado desde la aplicación, conservar SDK e
históricos para adaptar/migrar y retirar demostraciones de las vistas operativas.
No presentar score ni comparaciones de escalas sin contrato/modelo identificado.
Lectura SIC requiere su permiso además del acceso al cliente; listados no incluyen
payload ni mensajes crudos del proveedor. El origen histórico es desconocido.

Un adaptador productivo futuro deberá validar datos reales, consentimiento
vigente y evidencia/texto/proveedor, confirmar costo, firmar y verificar mensajes,
validar TLS y usar timeouts. Registrar operación antes de red, con idempotencia y
estados diferenciados. Timeout es resultado incierto: conciliar antes de reconsultar.
No usar reintentos automáticos sobre acciones posiblemente facturadas.

Separar datos normalizados (modelo, escala, fecha, procedencia) de respuesta
privada cifrada. Migración de históricos explícita y compatible, sin cambiar un
cast de JSON a cifrado sobre datos existentes. Credenciales fuera de BD/logs/props.
Productos deciden si requieren SIC; evaluación manual es política explícita,
nunca un score inventado ni un fallback silencioso ante fallo.

## Consecuencias

El circuito heredado queda temporalmente en lectura hasta P7; una variable de
entorno no lo convierte en seguro. Pruebas fake sin llamadas externas facturables.
Contrato/API, tarifas, vigencia, retención y textos deben validarse antes de abrirlo.
Referencia de seguridad: [CDC](https://developer.circulodecredito.com.mx/prueba_de_seguridad).
