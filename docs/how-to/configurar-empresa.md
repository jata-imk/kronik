---
type: how-to
area: configuration
status: active
---

# Configurar Empresa

## Requisitos

- Usuario autenticado.
- Equipo actual seleccionado.
- Permiso `read empresa-configuracion` para ver.
- Permiso `update empresa-configuracion` para guardar.

## Pasos

1. Ir a `Administración > Empresa`.
2. Capturar perfil legal, contacto y domicilio fiscal.
3. Definir moneda, zona horaria, horarios y folios.
4. Configurar días inhábiles, reglas de cobranza, formatos, cuentas y contactos.
5. Configurar integraciones usando nombres de variables de entorno, no secretos.
6. Activar la financiera solo cuando los datos obligatorios estén completos.
7. Guardar cambios.

## Validación

Si `Financiera activa` está habilitado, el sistema exige razón social, RFC, régimen fiscal, correo operativo y domicilio fiscal mínimo.

## Evidencia de Cierre

Para marcar cambios de este módulo como terminados:

- Ejecutar pruebas Pest del módulo.
- Tomar screenshot o correr e2e Playwright del flujo administrativo si hubo cambios visuales.
- Actualizar referencia, how-to y ADR si cambia la decisión de modelo.
- Comentar el avance en Notion.
