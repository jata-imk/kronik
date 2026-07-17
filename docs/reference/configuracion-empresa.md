---
type: reference
area: configuration
status: active
---

# Configuración de Empresa

## Modelo

La configuración de empresa se guarda en `empresa_configuraciones` y pertenece a un `team`.

Relación:

- `teams.id` 1:1 `empresa_configuraciones.team_id`

Campos principales:

- Perfil legal: `razon_social`, `nombre_comercial`, `rfc`, `regimen_fiscal_id`, `domicilio_fiscal`.
- Contacto: `email`, `telefono`, `sitio_web`, `logo_path`.
- Operación: `moneda`, `zona_horaria`, `horario_operacion`, `folio_credito_prefijo`, `folio_credito_siguiente`.
- Parámetros: `dias_inhabiles`, `reglas_cobranza`, `formatos_contrato`, `cuentas_bancarias`, `contactos`.
- Integraciones: `integraciones`.
- Activación: `activa`, `activated_at`.

## Activación

Para activar una financiera se requieren:

- Razón social.
- RFC válido.
- Régimen fiscal.
- Correo operativo.
- Calle, código postal, estado y país del domicilio fiscal.

## Credenciales Externas

La tabla no debe guardar secretos. Para integraciones se guardan referencias operativas:

- Círculo de Crédito: `integraciones.circulo_credito.env_prefix`, por ejemplo `CDC`.
- Geocoding: `integraciones.geocoding.env_key`, por ejemplo `GEOCODING_API_KEY`.

Los valores secretos viven en `.env` y deben documentarse en `.env.example` cuando sean requeridos.

## Permisos

Módulo Spatie: `empresa-configuracion`.

Permisos:

- `read empresa-configuracion`
- `update empresa-configuracion`

`Super Admin` conserva acceso implícito por `Gate::before`.

## Auditoría

Cada actualización registra actividad con descripción:

`Configuración de empresa actualizada`
