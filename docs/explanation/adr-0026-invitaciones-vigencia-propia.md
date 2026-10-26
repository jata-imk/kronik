# ADR 0026: vigencia propia de invitaciones

## Estado

Aceptado el 2026-10-25. [Tarea](https://app.notion.com/p/3fe61db7db7f81969ff5d3f224b3b2c5).

## Decisión

El broker `invitaciones` usa `user_invitation_tokens`, con token almacenado como
hash, 72 horas por defecto (`INVITATION_EXPIRE_MINUTES=4320`) y throttle de
60 segundos. No comparte almacenamiento ni vigencia con `users` (60 minutos).
La ruta de aceptación elige el broker en el servidor y exige cuenta pendiente.
Bloquea la cuenta durante la aceptación para impedir dos activaciones concurrentes.
Reutiliza validación de contraseña y finalización de Fortify, sin iniciar sesión.

Un reenvío sustituye el token anterior y elimina el enlace de recuperación legado.
La fecha de envío cambia solo tras un envío satisfactorio. Aceptar o recuperar
consume tokens de ambos flujos; una invitación no modifica cuentas activas o
inactivas. Los enlaces cortos anteriores conservan su vigencia original; no se
amplían ni se migran a 72 horas. La recuperación de una cuenta pendiente conserva
su flujo corto compatible y su texto de activación.

Las URLs de invitaciones Jetstream se sustituyen por respuestas explícitas 404.
Se conservan estos bloqueos en las rutas de la aplicación porque Jetstream
registra esas URLs con la función de equipos; no se copia ni modifica el paquete
para retirarlas. No existe UI de invitaciones Jetstream ni aceptación paralela.

## Consecuencias

El listado obtiene vigencia del registro de token y su broker, sin inferirla del
estado de la cuenta. La ausencia de token no se presenta como enlace válido.
La migración es aditiva; un rollback requiere reenviar invitaciones por el flujo
anterior, no copiar tokens con duración ampliada a recuperación.
El reinicio de QA clasifica la tabla nueva como datos de acceso a limpiar,
conservando sus protecciones y eliminando tokens que ya no tengan cuenta vigente.
