---
type: how-to
area: catalogs
status: active
---

# Restaurar catalogos y datos de desarrollo

Use este procedimiento despues de `migrate:fresh` o cuando los conteos de catalogos sean cero.

## Requisitos

- Ejecutar desde la misma version del codigo que corresponde al esquema.
- Tener acceso a MySQL.
- Tener salida HTTP hacia SAT y Correos de Mexico si no existen archivos en `storage/app`.
- Tener `libreoffice` disponible cuando el catalogo SAT solo este en formato
  XLS. En Debian 12 puede instalarse con:

```bash
sudo apt install libreoffice-calc-nogui fonts-dejavu-core fonts-liberation
```

Consulte [Desplegar Kronik en una VPS Debian 12](desplegar-vps-nuevo.md) para
preparar CloudPanel, PHP, workers, respaldos y el resto de dependencias.

## Restauracion completa

```bash
php artisan migrate:fresh --force
php artisan db:seed --class=PaisesSeeder --force
php artisan sat-cfdi-v4:update
php artisan sepomex:update
php artisan db:seed --class=SystemSeeder --force
php artisan db:seed --class=DevelopmentSeeder --force
```

No ejecute `SystemSeeder` o `DevelopmentSeeder` antes de los catalogos: empresa, clientes y domicilios dependen de paises, regimenes y SEPOMEX.

`sepomex:update` reutiliza el TXT descargado cuando existe y restaura Mexico por lotes. Durante una restauracion reemplaza solamente las divisiones y codigos postales mexicanos.

## Verificacion

```bash
php artisan tinker --execute="dump([
    'paises' => DB::table('paises')->count(),
    'regimenes' => DB::table('regimenes_fiscales')->count(),
    'divisiones' => DB::table('divisiones_administrativas')->count(),
    'codigos_postales' => DB::table('codigos_postales')->count(),
    'merida_97000' => DB::table('codigos_postales')->where('codigo', '97000')->count(),
]);"
```

La carga es valida cuando los conteos principales son mayores que cero y `merida_97000` devuelve al menos un asentamiento.

## Restaurar solo datos demo

Cuando los catalogos ya existen, no use `migrate:fresh`. Ejecute:

```bash
php artisan dev:reset-data
```
