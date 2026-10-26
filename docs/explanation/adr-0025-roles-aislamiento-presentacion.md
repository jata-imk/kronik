# ADR 0025: aislamiento y presentación de roles

## Estado

Aceptado el 2026-10-25. [Tarea](https://app.notion.com/p/3fe61db7db7f81a1a945fdc60db68fc4).

## Decisión

El servidor determina el equipo de las escrituras de Roles; el cuerpo no puede
crear plantillas ni elegir otro equipo. Actualizar y eliminar exige coincidencia
con el contexto autorizado, incluso para Super Admin. Las plantillas globales se
administran mediante seeders y se copian al crear equipos, sin nueva pantalla.

El nombre es único por equipo y guard, como exige el índice existente. Omitir
`permissions` conserva permisos; enviar `[]` los retira todos. No se elimina un
rol con permisos o usuarios. Las escrituras de nombre y permisos son atómicas.

Las claves técnicas no cambian. `etiqueta` y `descripcion` proporcionan la
presentación en español. Un catálogo común completa los registros existentes,
los seeders y nuevas altas; los personalizados reciben textos neutrales en
español y pueden definir su propia etiqueta y descripción. La migración es
aditiva y no reescribe migraciones históricas.

## Consecuencias

Los Gates y clientes que consumen `auth.permissions` conservan sus claves.
Las ayudas explican la acción sin prometer un alcance de datos distinto de las
políticas vigentes. La UI distingue consulta de escritura; la protección real
permanece en el servidor. Administrar un equipo seleccionado será una extensión
explícita del contexto autorizado, sin confiar en `team_id` del cuerpo.
