---
type: how-to
area: development
status: active
---

# Reset de Datos de Desarrollo

Usa este flujo cuando quieras volver a datos de prueba sin esperar a que se descarguen otra vez los catálogos pesados del SAT y SEPOMEX.

## Reset rápido

```bash
php artisan dev:reset-data
```

El comando limpia datos volátiles y vuelve a ejecutar `DevelopmentSeeder`. Conserva:

- `paises`
- `regimenes_fiscales`
- `divisiones_administrativas`
- `codigos_postales`
- `migrations`
- datos de sistema como módulos, permisos, roles, menubar y SICs

## Reset sin resembrar usuario de prueba

```bash
php artisan dev:reset-data --no-seed
```

## Cuándo usar `migrate:fresh --seed`

Usa este comando solo cuando quieras reconstruir toda la estructura y aceptar que SAT/SEPOMEX se vuelvan a poblar:

```bash
php artisan migrate:fresh --seed
```

Ese flujo borra todas las tablas. En una base vacía ejecuta `CatalogSeeder`, `SystemSeeder` y `DevelopmentSeeder`.

## Tablas que se limpian

El reset rápido limpia usuarios, equipos, clientes, direcciones, consultas SIC, sesiones, jobs y bitácora de actividad. No toca catálogos SAT/SEPOMEX para evitar resets de 30 minutos.

## Datos que quedan después del reset

El seeder deja datos demo mínimos. Si la BD todavía no tiene catálogos, `DevelopmentSeeder` agrega fixtures mínimos de México, régimen fiscal y un CP CDMX para que existan clientes/direcciones de prueba sin esperar el scraper completo.

- Usuario `test@example.com`.
- Equipo personal para el usuario demo.
- Rol global `Super Admin` asignado al usuario demo.
- Roles scoped para el equipo demo, con permisos base.
- Fixtures mínimos de catálogo cuando la BD está vacía.
- Clientes de ejemplo con datos fiscales.
- Dirección vinculada al catálogo SEPOMEX.
- Consultas SIC fake para probar historial crediticio sin llamadas externas.

Relacionado:

- [Seeders](../reference/seeders.md)
- [Comandos Artisan](../reference/comandos-artisan.md)
- [Decisiones técnicas](../explanation/decisiones.md)
