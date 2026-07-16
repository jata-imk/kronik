---
type: how-to
area: deployment
status: active
---

# Migrar VPS

## Flujo recomendado

1. Instalar PHP 8.2+, Composer, Node/npm, MariaDB/MySQL, Nginx o Apache, PHP-FPM y extensiones PHP requeridas, incluyendo `intl`.
2. Clonar el repositorio en la nueva VPS.
3. Copiar `.env` desde la VPS anterior y conservar el mismo `APP_KEY`.
4. Restaurar dump de base de datos desde la VPS anterior.
5. Copiar `storage/app` si hay archivos subidos o generados.
6. Instalar dependencias y compilar assets:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

7. Ejecutar tareas Laravel:

```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

8. Configurar el virtual host apuntando a `public/`.
9. Configurar worker de cola con Supervisor o systemd:

```bash
php artisan queue:work
```

## Catálogos

No ejecutes `migrate:fresh --seed` en una migración real. Ese comando borra la base completa. Para conservar catálogos SAT/SEPOMEX y datos reales, restaura la base anterior y luego ejecuta únicamente migraciones pendientes.

Relacionado:

- [Variables de entorno](../reference/variables-entorno.md)
- [Comandos Artisan](../reference/comandos-artisan.md)
- [Reset de datos de desarrollo](reset-datos-desarrollo.md)
