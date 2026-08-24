---
type: how-to
area: deployment
status: active
---

# Desplegar Kronik en una VPS Debian 12

Última revisión operativa: 2026-07-24.

Esta guía instala una instancia de Kronik para una financiera. Cubre una VPS
vacía y una VPS que ya administra sitios con CloudPanel o Plesk.

Los ejemplos usan:

- Dominio: `kronik.example.com`.
- Usuario del sitio: `kronik`.
- Ruta: `/home/kronik/htdocs/kronik.example.com`.
- PHP: 8.3 en CloudPanel; PHP 8.2 o superior es compatible.
- Rama: `main`.

Sustituya esos valores por los reales. Ejecute comandos de aplicación como el
usuario del sitio, nunca como `root`.

## 1. Elegir la ruta

### CloudPanel o Plesk ya está instalado

Omita la instalación manual de Nginx, MariaDB, PHP-FPM, firewall y Certbot.
Instalar una segunda copia puede romper los servicios administrados por el
panel.

Continúe en:

- [Ruta CloudPanel](#3-ruta-cloudpanel-recomendada-para-esta-vps), o
- [Ruta Plesk](#4-ruta-plesk).

Después retome el procedimiento común en
[Preparar el repositorio](#5-preparar-el-repositorio).

### No hay panel

Complete la preparación manual del servidor y configure Nginx antes de
continuar.

## 2. Preparar Debian 12 sin panel

Esta sección requiere un usuario con `sudo`.

### 2.1 Actualizar e instalar servicios

```bash
sudo apt update
sudo apt upgrade
sudo apt install nginx mariadb-server supervisor git unzip curl ca-certificates composer ufw
sudo apt install php8.2-fpm php8.2-cli php8.2-mysql php8.2-curl php8.2-mbstring php8.2-xml php8.2-zip php8.2-bcmath php8.2-soap php8.2-intl php8.2-gd
```

Laravel 11 requiere PHP 8.2 o posterior. Las extensiones anteriores cubren los
requisitos de Laravel y las extensiones usadas por la CI del proyecto.

Proteja MariaDB y cree un usuario de sistema dedicado:

```bash
sudo mariadb-secure-installation
sudo adduser --disabled-password --gecos "" kronik
```

Configure el firewall para permitir únicamente SSH, HTTP y HTTPS:

```bash
sudo ufw allow OpenSSH
sudo ufw allow "Nginx Full"
sudo ufw enable
sudo ufw status
```

Restrinja SSH a IPs conocidas cuando la operación lo permita. Confirme una
segunda sesión SSH antes de cerrar la primera para evitar perder acceso.

### 2.2 Instalar Node.js

El frontend se compila con Node.js 22. Como usuario `kronik`, instale la versión
de NVM fijada al documentar esta guía:

```bash
git clone https://github.com/nvm-sh/nvm.git ~/.nvm
git -C ~/.nvm checkout v0.40.4
source ~/.nvm/nvm.sh
nvm install 22
nvm alias default 22
node --version
npm --version
```

Agregue la carga de `~/.nvm/nvm.sh` al perfil shell del usuario y revise la
versión vigente de NVM antes de repetir esta instalación en el futuro.

No se necesita Node.js para atender peticiones después de generar
`public/build`, pero sí para cada compilación realizada en la VPS.

### 2.3 Crear base de datos

Entre a MariaDB como administrador:

```bash
sudo mariadb
```

Ejemplo; reemplace la contraseña:

```sql
CREATE DATABASE kronik CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'kronik'@'127.0.0.1' IDENTIFIED BY 'CONTRASENA-LARGA-Y-UNICA';
GRANT ALL PRIVILEGES ON kronik.* TO 'kronik'@'127.0.0.1';
FLUSH PRIVILEGES;
EXIT;
```

No exponga el puerto 3306 a Internet.

### 2.4 Configurar Nginx

El document root debe ser `public`, nunca la raíz del repositorio:

```nginx
server {
    listen 80;
    server_name kronik.example.com;
    root /home/kronik/htdocs/kronik.example.com/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Guarde el bloque en `/etc/nginx/sites-available/kronik` y habilítelo:

```bash
sudo ln -s /etc/nginx/sites-available/kronik /etc/nginx/sites-enabled/kronik
sudo nginx -t
sudo systemctl reload nginx
```

Después de configurar DNS, instale y ejecute Certbot:

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d kronik.example.com
```

No establezca `APP_URL=https://...` ni `SESSION_SECURE_COOKIE=true` hasta que
HTTPS funcione.

## 3. Ruta CloudPanel recomendada para esta VPS

CloudPanel ya administra Nginx, PHP-FPM, MariaDB, certificados y firewall.
Debian 12 está soportado por CloudPanel.

### 3.1 Revisar la instancia

En CloudPanel:

1. Actualice CloudPanel y Debian dentro de una ventana de mantenimiento.
2. Confirme que los puertos 22 y 8443 estén restringidos a IPs de
   administración cuando sea posible.
3. Confirme espacio libre para código, catálogos, logs, documentos KYC y
   respaldos.

### 3.2 Crear el sitio PHP

1. Entre a **Sites > Add Site > Create a PHP Site**.
2. Elija la plantilla Laravel disponible; si no existe, use **Generic**.
3. Capture `kronik.example.com`.
4. Use PHP 8.3.
5. Cree el usuario de sitio `kronik` y guarde sus credenciales.
6. En la configuración del sitio, establezca el document root en el directorio
   `public` de la aplicación.

CloudPanel crea el usuario SSH y su directorio bajo `/home/kronik`.

### 3.3 Crear la base de datos

En **Databases > Add Database**:

1. Cree la base `kronik`.
2. Cree un usuario exclusivo para esa base.
3. Genere una contraseña larga y guárdela en el gestor de secretos.
4. No habilite acceso remoto salvo que exista una necesidad operativa y una
   lista blanca de IPs.

Use las credenciales resultantes en `.env`.

### 3.4 Configurar DNS y TLS

1. Cree el registro DNS `A` o `AAAA` hacia la VPS.
2. Espere a que resuelva desde Internet.
3. En **SSL/TLS**, emita e instale un certificado Let's Encrypt.
4. Si usa Cloudflare, configure el modo TLS **Full** o **Full (strict)** y evite
   que el origen quede accesible por fuera de Cloudflare cuando sea viable.

CloudPanel crea inicialmente un certificado autofirmado; no lo considere
válido para producción.

## 4. Ruta Plesk

Si Plesk ya está instalado, omita también la sección 2:

1. Cree el dominio o suscripción.
2. Seleccione PHP 8.2 o superior.
3. Habilite SSH para el usuario de la suscripción.
4. Use Laravel Toolkit cuando esté disponible.
5. Establezca el document root en `httpdocs/public` o en el `public` de la
   ruta donde clone Kronik.
6. Cree la base y su usuario desde Plesk.
7. Emita el certificado TLS desde Plesk.
8. Configure las tareas programadas desde Laravel Toolkit o Scheduled Tasks.

Los comandos restantes son iguales; adapte usuario, binario PHP y rutas.

## 5. Preparar el repositorio

### 5.1 Configurar acceso de solo lectura a GitHub

Como usuario del sitio:

```bash
ssh-keygen -t ed25519 -f ~/.ssh/kronik-deploy -C "kronik-production"
cat ~/.ssh/kronik-deploy.pub
```

Agregue la clave pública como **Deploy key** de solo lectura en GitHub. Después
cree `~/.ssh/config`:

```sshconfig
Host github.com
    HostName github.com
    User git
    IdentityFile ~/.ssh/kronik-deploy
    IdentitiesOnly yes
```

Proteja los archivos y compruebe la conexión:

```bash
chmod 700 ~/.ssh
chmod 600 ~/.ssh/config ~/.ssh/kronik-deploy
chmod 644 ~/.ssh/kronik-deploy.pub
ssh -T git@github.com
```

### 5.2 Clonar

CloudPanel puede haber creado un directorio de ejemplo. Consérvelo hasta
confirmar que no contiene información necesaria:

```bash
cd ~/htdocs
mv kronik.example.com kronik.example.com.cloudpanel-placeholder
git clone --branch main git@github.com:jata-imk/kronik.git kronik.example.com
cd kronik.example.com
```

Después de validar el despliegue puede retirar el placeholder.

En Plesk o una VPS sin panel, clone en la ruta elegida y mantenga el document
root apuntando a `public`.

## 6. Instalar dependencias del sistema

### 6.1 LibreOffice: requerido actualmente

El importador de catálogos SAT convierte XLS a XLSX ejecutando
`libreoffice --headless`. En Debian 12 instale la variante de Calc sin interfaz:

```bash
sudo apt update
sudo apt install libreoffice-calc-nogui fonts-dejavu-core fonts-liberation tmux
libreoffice --headless --version
```

Si el comando `sat-cfdi-v4:update` encuentra un XLS y LibreOffice no está
disponible, la restauración de regímenes fiscales fallará. No hace falta una
interfaz gráfica.

### 6.2 Chromium/Puppeteer para documentos PDF

Backlog 03.5 genera PDF con Chromium mediante Puppeteer y Browsershot.
`npm ci` instala Puppeteer; asegure que su Chromium y las bibliotecas del
sistema estén disponibles para el mismo usuario que ejecuta el worker:

```bash
cd ~/htdocs/kronik.example.com
sudo env "PATH=$PATH" ./node_modules/.bin/puppeteer browsers install chrome --install-deps
./node_modules/.bin/puppeteer browsers install chrome
./node_modules/.bin/puppeteer browsers list
php artisan documentos:benchmark-pdf --runs=5
```

La variante `--install-deps` es exclusiva de Debian/Ubuntu y requiere
privilegios para instalar paquetes del sistema. El segundo comando garantiza
que Chrome también quede en la caché del usuario del sitio; no ejecute el
worker como `root`.

Si Node, los módulos o Chrome no están en rutas estándar, configure
`DOCUMENTOS_NODE_BINARY`, `DOCUMENTOS_NPM_BINARY`,
`DOCUMENTOS_NODE_MODULES_PATH` y `DOCUMENTOS_CHROME_PATH`. Mantenga el sandbox
de Chromium habilitado. El benchmark debe quedar debajo de 10 segundos por PDF
y sirve para decidir la concurrencia del worker según la memoria de la VPS.

`wkhtmltopdf` está descartado y Pandoc no se instala mientras DOCX siga fuera
del alcance confirmado.

### 6.3 Verificar PHP, Composer y Node

CloudPanel proporciona binarios versionados. Use el binario que corresponda al
PHP seleccionado:

```bash
php8.3 --version
php8.3 -m
/usr/local/bin/composer --version
node --version
npm --version
```

Se requieren, como mínimo, las extensiones `ctype`, `curl`, `dom`, `fileinfo`,
`filter`, `hash`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `session`,
`tokenizer` y `xml`. Kronik también usa en CI `zip`, `pcntl`, `bcmath`, `soap`,
`intl` y `gd`.

Si Node no está disponible para el usuario del sitio, instale Node 22 con NVM
como se describe en 2.2.

## 7. Configurar `.env`

Copie la plantilla:

```bash
cp .env.example .env
nano .env
```

Base recomendada:

```dotenv
APP_NAME=Kronik
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=America/Mexico_City
APP_URL=https://kronik.example.com

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_MX

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kronik
DB_USERNAME=kronik
DB_PASSWORD=CONTRASENA-LARGA-Y-UNICA

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

MAIL_MAILER=log
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="${APP_NAME}"

CIRCULO_CREDITO_HOST=
CIRCULO_CREDITO_API_KEY=
CIRCULO_CREDITO_SANDBOX=true
```

Configure SMTP antes de enviar correos reales; `MAIL_MAILER=log` solo escribe
mensajes en logs.

Reglas:

- No suba `.env` a Git.
- Genere `APP_KEY` una sola vez y respáldela en un gestor de secretos.
- Perder `APP_KEY` impide descifrar datos cifrados.
- No reutilice credenciales de desarrollo o CI.
- Mantenga `APP_DEBUG=false` en un dominio público.

## 8. Instalar Kronik

### 8.1 Dependencias PHP y frontend

En CloudPanel:

```bash
php8.3 /usr/local/bin/composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
```

Sin panel use `php` y `composer` si son los binarios correctos.

Compruebe las extensiones después de Composer:

```bash
php8.3 /usr/local/bin/composer check-platform-reqs --no-dev
```

### 8.2 Inicializar la aplicación

Solo en la primera instalación:

```bash
php8.3 artisan key:generate --force
php8.3 artisan migrate --force
php8.3 artisan storage:link
```

No vuelva a ejecutar `key:generate` en despliegues posteriores.

Conceda escritura al usuario del sitio en:

```bash
chmod -R ug+rwX storage bootstrap/cache
```

En CloudPanel no cambie indiscriminadamente el propietario a `www-data`: el
PHP-FPM del sitio trabaja con el usuario creado por CloudPanel.

### 8.3 Cargar catálogos y datos iniciales

Para una instancia vacía:

```bash
php8.3 artisan db:seed --class=PaisesSeeder --force
php8.3 artisan sat-cfdi-v4:update
php8.3 artisan sepomex:update
php8.3 artisan db:seed --class=SystemSeeder --force
```

Solo si el entorno usa exclusivamente datos de prueba:

```bash
php8.3 artisan db:seed --class=DevelopmentSeeder --force
```

No ejecute `DevelopmentSeeder` en una instancia con operación o datos reales.
No use `migrate:fresh` para actualizar una instalación existente.

La carga SEPOMEX puede tardar. Ejecútela en una sesión persistente como
`tmux`/`screen` o desde la consola de CloudPanel sin cerrar la conexión.

Consulte [Restaurar catálogos](restaurar-catalogos.md) para verificar conteos y
el código postal de Mérida.

### 8.4 Optimizar

```bash
php8.3 artisan optimize:clear
php8.3 artisan optimize
```

## 9. Configurar procesos permanentes

### 9.1 Worker de cola

Kronik usa por defecto la cola `database`. Instale Supervisor como `root` si el
panel no ofrece un administrador de procesos:

```bash
sudo apt install supervisor
```

Cree `/etc/supervisor/conf.d/kronik-worker.conf` y ajuste binario, usuario y
ruta:

```ini
[program:kronik-worker]
process_name=%(program_name)s_%(process_num)02d
directory=/home/kronik/htdocs/kronik.example.com
command=/usr/bin/php8.3 artisan queue:work database --sleep=3 --tries=3 --timeout=60 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=kronik
numprocs=1
redirect_stderr=true
stdout_logfile=/home/kronik/htdocs/kronik.example.com/storage/logs/worker.log
stopwaitsecs=3600
```

Active y compruebe:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status kronik-worker:*
```

El `stopwaitsecs` debe superar la duración del trabajo de cola más largo.
`--timeout` debe permanecer por debajo de `DB_QUEUE_RETRY_AFTER`, cuyo valor
predeterminado es 90 segundos, para evitar que un trabajo sea procesado dos
veces.

### 9.2 Scheduler

Actualmente Kronik no define tareas programadas de negocio, pero deje preparado
el scheduler para futuros comandos.

En CloudPanel abra **Cron Jobs**, elija ejecución cada minuto y use:

```bash
cd /home/kronik/htdocs/kronik.example.com && /usr/bin/php8.3 artisan schedule:run
```

Sin panel agregue al `crontab` del usuario `kronik`:

```cron
* * * * * cd /home/kronik/htdocs/kronik.example.com && /usr/bin/php8.3 artisan schedule:run >> /dev/null 2>&1
```

## 10. Respaldos

Respalde conjuntamente:

- Base MariaDB.
- `.env` y `APP_KEY`, en un gestor de secretos.
- `storage/app/private`, que contiene documentos KYC y evidencias SIC.
- `storage/app/public`, si contiene archivos de usuario.
- Configuración de Nginx, Supervisor, cron y panel.

CloudPanel crea respaldos nocturnos de base con retención limitada y permite
respaldos remotos mediante Rclone. Configure una copia fuera de la VPS y pruebe
una restauración; un respaldo que solo existe en el mismo disco no cubre la
pérdida de la instancia.

## 11. Validar el despliegue

```bash
php8.3 artisan about
php8.3 artisan migrate:status
php8.3 artisan queue:failed
curl --fail https://kronik.example.com/up
```

Verifique manualmente:

1. HTTPS válido y redirección desde HTTP.
2. Inicio de sesión.
3. Carga de assets sin errores 404.
4. Configuración de empresa.
5. Países, regímenes y código postal `97000`.
6. Alta de cliente y expediente KYC.
7. Subida y descarga privada de un documento.
8. Worker activo y logs sin errores.
9. Respaldo remoto confirmado.

## 12. Despliegues posteriores

Antes de actualizar, cree respaldo. Después, como usuario del sitio:

```bash
cd /home/kronik/htdocs/kronik.example.com
php8.3 artisan down --retry=60
git pull --ff-only origin main
php8.3 /usr/local/bin/composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
php8.3 artisan migrate:status
php8.3 artisan migrate --force
php8.3 artisan db:seed --class=ModulesAndPermissionsSeeder --force
php8.3 artisan db:seed --class=MenubarItemsSeeder --force
php8.3 artisan permission:cache-reset
php8.3 artisan optimize:clear
php8.3 artisan optimize
php8.3 artisan queue:restart
php8.3 artisan up
```

Los dos seeders son idempotentes y deben ejecutarse cuando una versión agrega
módulos, permisos o entradas canónicas del menú. Crean los permisos de
documentos, pero no los conceden automáticamente a roles personalizados;
revise esos roles desde la administración después del despliegue.

Si el despliegue falla mientras está en mantenimiento, corrija o restaure la
versión y el respaldo antes de ejecutar `artisan up`. No use
`migrate:rollback` automáticamente: una migración puede haber transformado
datos y requerir un procedimiento específico.

Para despliegues atómicos y rollback por releases, CloudPanel ofrece `dploy`.
Adóptelo como una mejora posterior; el primer despliegue puede operar con el
flujo directo anterior.

## Fuentes operativas

- [Laravel 11: despliegue](https://laravel.com/docs/11.x/deployment).
- [Laravel 11: colas y Supervisor](https://laravel.com/docs/11.x/queues).
- [Laravel 11: scheduler](https://laravel.com/docs/11.x/scheduling).
- [CloudPanel: crear sitio PHP](https://www.cloudpanel.io/docs/v2/frontend-area/add-site/).
- [CloudPanel: aplicaciones Laravel](https://www.cloudpanel.io/docs/v2/php/applications/laravel/).
- [CloudPanel: SSL/TLS](https://www.cloudpanel.io/docs/v2/frontend-area/tls/).
- [CloudPanel: cron](https://www.cloudpanel.io/docs/v2/frontend-area/cron-jobs/).
- [CloudPanel: bases y respaldos](https://www.cloudpanel.io/docs/v2/frontend-area/databases/).
- [CloudPanel: respaldos remotos](https://www.cloudpanel.io/docs/v2/admin-area/backups/).
- [CloudPanel: dploy](https://www.cloudpanel.io/docs/v2/dploy/installation/).
- [Plesk: Laravel Toolkit](https://docs.plesk.com/en-US/obsidian/administrator-guide/website-management/laravel-toolkit.80010/).
- [Debian 12: LibreOffice sin GUI](https://packages.debian.org/bookworm/metapackages/libreoffice-nogui).
- [NVM: instalación oficial](https://github.com/nvm-sh/nvm).
