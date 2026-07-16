---
type: reference
area: database
status: active
---

# Seeders

## Estructura

- `CatalogSeeder`: catálogos base y pesados, incluyendo países, SAT CFDI y SEPOMEX.
- `SystemSeeder`: datos de sistema, módulos, permisos, roles, menubar y SICs.
- `DevelopmentSeeder`: datos de prueba para desarrollo: usuario demo, equipo personal, clientes, datos fiscales, dirección y consultas SIC fake.
- `DatabaseSeeder`: ejecuta los tres anteriores para una base nueva.

## Idempotencia

Los seeders de sistema deben poder re-ejecutarse sin duplicar registros. Para eso usan `firstOrCreate`, `updateOrCreate` o `upsert`.

Los catálogos SAT/SEPOMEX se conservan durante resets rápidos. Si necesitas forzar una actualización externa, usa:

```bash
php artisan sat-cfdi-v4:update
php artisan sepomex:update
```

## Reset de Desarrollo

Para volver a datos de prueba sin borrar SAT/SEPOMEX:

```bash
php artisan dev:reset-data
```

Después del reset, `DevelopmentSeeder` intenta crear datos representativos de los módulos disponibles. Si los catálogos SAT/SEPOMEX todavía no existen, mantiene el usuario/equipo demo y omite clientes/direcciones para no fallar.

Datos demo actuales:

- `test@example.com` con contraseña de factory `password`.
- Cliente persona física con datos fiscales, dirección y score SIC exitoso.
- Cliente con datos fiscales tipo moral y consulta SIC pendiente.

Relacionado:

- [Reset de datos de desarrollo](../how-to/reset-datos-desarrollo.md)
- [Comandos Artisan](comandos-artisan.md)
- [Decisiones técnicas](../explanation/decisiones.md)
