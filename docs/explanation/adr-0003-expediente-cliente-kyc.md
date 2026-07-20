# ADR 0003: Expediente de cliente y KYC

## Estado

Aceptado el 2026-07-19.

## Contexto

El alta de clientes solo contenia identidad, datos fiscales y domicilio. Backlog 02 requiere perfil economico, documentos, referencias, personas vinculadas, garantias y consentimiento SIC antes de construir solicitudes de credito.

La tabla `direcciones` guarda el alias polimorfico `clientes`, pero `Cliente` lo devolvia mediante un override local de `getMorphClass()`. Esto permitia escribir el alias, pero no garantizaba que un `morphTo` pudiera resolverlo.

## Decision

- El expediente pertenece al cliente. Los vinculos especificos por solicitud se agregaran cuando Backlog 04 introduzca `solicitudes_credito`.
- Los campos economicos mensuales viven en `clientes`; las colecciones con ciclo propio usan tablas relacionadas.
- Los documentos conservan versiones. Los cuatro tipos base se crean como checklist pendiente y una sustitucion no elimina la version previa.
- Documentos y evidencias SIC se guardan en el disco privado `local`. Solo se descargan por rutas autenticadas y autorizadas.
- Los estados y tipos se validan con enums PHP y columnas string para facilitar cambios sin alterar enums de MariaDB.
- El alias morph estable se registra con `Relation::morphMap(['clientes' => Cliente::class])`. No se usa `enforceMorphMap` porque activity log contiene otros modelos polimorficos con nombres de clase completos.
- El consentimiento registra evidencia y trazabilidad, pero Backlog 02 no determina vigencia legal ni bloquea consultas SIC. Ese control pertenece a Backlog 05.
- OCR e integraciones con proveedores KYC permanecen fuera de esta entrega.

## Consecuencias

- Backlog 04 debera decidir como reutilizar avales y garantias del expediente en cada solicitud sin mutar el historial del cliente.
- Backlog 05 podra consultar consentimientos vigentes, pero debe definir reglas legales y textos versionados antes de bloquear llamadas.
- La eliminacion de un cliente limpia sus archivos privados y relaciones; los accesos y cambios relevantes quedan en activity log.
