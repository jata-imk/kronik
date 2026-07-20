# Seeders

## Estructura

- `CatalogSeeder`: catalogos base y pesados, incluyendo paises, SAT CFDI y SEPOMEX.
- `SystemSeeder`: datos de sistema, modulos, permisos, roles globales, menubar y SICs.
- `DevelopmentSeeder`: datos demo para desarrollo.
- `DatabaseSeeder`: ejecuta catalogos, sistema y desarrollo.

## Idempotencia

Los seeders de sistema y desarrollo deben poder re-ejecutarse sin duplicar registros. Para eso usan `firstOrCreate`, `updateOrCreate` o sincronizacion de permisos.

## Reset de Desarrollo

Para volver a datos de prueba sin borrar SAT/SEPOMEX:

```bash
php artisan dev:reset-data
```

Datos demo actuales:

- `test@example.com` con password de factory `password`.
- Empresa demo singleton.
- Sucursal `MATRIZ`.
- Cliente persona fisica con score SIC exitoso.
- Cliente tipo moral con consulta SIC pendiente.
- Perfil economico KYC en ambos clientes demo.
- Checklist documental pendiente, referencia personal y ejemplo de aval/garantia.

Relacionado:

- [Reset de datos de desarrollo](../how-to/reset-datos-desarrollo.md)
- [Comandos Artisan](comandos-artisan.md)
