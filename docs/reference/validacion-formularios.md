# Validacion de Formularios

## Regla obligatoria

Ninguna clave interna de Laravel como `validation.required`, `validation.integer` o `validation.in` puede mostrarse al usuario. La aplicacion usa espanol como locale y fallback en `config/app.php`; las traducciones generales viven en `lang/es/validation.php`.

## Responsabilidades

- Toda regla nueva debe tener una traduccion general o un mensaje especifico del `FormRequest`.
- Los nombres tecnicos y anidados deben mapearse en `attributes` cuando aparezcan en el mensaje.
- Los mensajes de negocio deben explicar como corregir el dato, por ejemplo seleccionar una colonia valida o reasignar una garantia.
- El frontend debe mostrar los errores devueltos por Inertia y solo emitir un mensaje de exito desde `onSuccess`.
- Los formularios largos deben desplazar el foco o la vista al primer campo invalido y mostrar un resumen mediante toast.

## Verificacion

Las pruebas de flujos visibles deben comprobar el texto relevante con `assertSessionHasErrors`. Durante QA manual, provocar al menos un error por formulario y confirmar que no aparece ninguna cadena que empiece con `validation.`.
