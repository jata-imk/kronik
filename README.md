# Sistema ERP Financiero

ERP financiero orientado a pequeñas financieras y fintech mexicanas que operan productos de crédito. El supuesto funcional principal es una **SOFOM ENR**, con soporte inicial para crédito simple y roadmap temprano para crédito revolvente.

El despliegue esperado es **una app por financiera**. Si otra financiera contrata el sistema, se despliega otra instancia/VPS con su propia configuración.

La plataforma busca centralizar configuración de empresa, clientes, expedientes KYC, consultas a Sociedades de Información Crediticia (SIC), originación, PLD, amortización, cobranza, reportes y auditoría.

## Estado Actual

El repositorio ya cuenta con una base Laravel 11 + Inertia + Vue 3:

- Autenticación y administración central de usuarios, con invitaciones, estados y Super Admin global.
- Equipos Jetstream como contexto de roles y permisos Spatie, separados de las sucursales operativas.
- Asignación de usuarios a varias sucursales, con sucursal principal y actual.
- Configuración global de empresa y sucursales operativas.
- Alta, edición, consulta, traslado y eliminación de clientes con sucursal responsable.
- Datos fiscales, domicilios, catálogos de países, régimen fiscal y códigos postales.
- Integración parcial con Círculo de Crédito para FICO Score, Fintech Score y reportes relacionados.
- Dashboard de historial crediticio con consultas SIC almacenadas.
- Menubar configurable por base de datos.
- Bitácora de actividad con `spatie/laravel-activitylog`.

## Alcance Funcional Objetivo

### Configuración Global de Empresa

Cada instancia debe configurar razón social, datos fiscales, usuarios, roles, permisos, moneda, zona horaria, parámetros de cobranza, formatos contractuales, llaves de integraciones y políticas de operación. Las sucursales guardan domicilio, horarios y folios propios. Los `teams` quedan para departamentos, grupos de trabajo y contexto de permisos.

### Productos de Crédito

El primer motor debe cubrir **crédito simple**: monto, plazo, tasa ordinaria, tasa moratoria, comisiones, periodicidad, días de gracia, tabla de amortización, reglas de prepago y desembolso.

El segundo motor debe cubrir **crédito revolvente**: línea autorizada, disposiciones, cortes, pagos mínimos, intereses por saldo, límite disponible, cargos, estados de cuenta y CAT revolvente.

### Solicitantes, KYC y Expediente

El sistema debe extender el cliente actual hacia expediente digital: identificación oficial, comprobante de domicilio, ingresos, actividad económica, referencias, documentos, avales, beneficiario/control cuando aplique, consentimiento para consulta SIC y evidencia de aceptación de aviso de privacidad.

### Originación y Riesgo

El flujo objetivo incluye solicitud, validación documental, consulta SIC, score, capacidad de pago, reglas de decisión, dictamen, aprobación, rechazo, contrato, firma, desembolso y seguimiento posterior.

### PLD y Cumplimiento

Para SOFOM ENR, el sistema debe soportar expediente de identificación, perfil transaccional, clasificación de riesgo, listas, alertas, operaciones inusuales/relevantes, bitácoras, evidencia documental y reportes internos. La configuración debe permitir adaptar políticas por financiera.

### Operación Crediticia

Se deben implementar tablas de amortización, pagos, aplicación de pagos, intereses ordinarios, intereses moratorios, comisiones, cobranza preventiva, cartera vencida, reestructuras, convenios, condonaciones controladas y reportes operativos.

## Stack Tecnológico

- **Backend:** Laravel 11, PHP 8.2+, Sanctum, Jetstream, Spatie Permission, Spatie Activitylog.
- **Frontend:** Vue 3, Inertia.js, Vite, Tailwind CSS, PrimeVue, Chart.js.
- **Base de datos:** MariaDB.
- **Herramientas:** Composer, npm, Biome, Laravel Pint, Pest/PHPUnit.

## Instalación y Desarrollo

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
composer dev
```

Comandos útiles:

- `composer dev`: inicia servidor Laravel, queue listener y Vite.
- `npm run dev`: inicia Vite.
- `npm run build`: compila assets de producción.
- `php artisan test` o `vendor/bin/pest`: ejecuta pruebas.
- `vendor/bin/pint`: formatea PHP.
- `npm run format`: formatea archivos frontend con Biome.

## Despliegue

La guía para una VPS Debian 12, incluyendo CloudPanel, Plesk, Nginx, MariaDB,
TLS, workers, scheduler, respaldos y catálogos, está en
[`docs/how-to/desplegar-vps-nuevo.md`](docs/how-to/desplegar-vps-nuevo.md).

Dependencias externas:

- LibreOffice Calc sin GUI: requerido actualmente para convertir el catálogo
  SAT de XLS a XLSX.
- wkhtmltopdf y Pandoc: opcionales hasta que un flujo de generación documental
  los invoque.

## Roadmap Prioritario

1. **Documentación y diseño funcional:** convertir el análisis financiero en TODOs implementables por módulo.
2. **Configuración de empresa:** perfil de financiera, sucursales, folios, parámetros globales e integraciones.
3. **Expediente de cliente/KYC:** completar solicitantes, documentos, consentimientos, avales, referencias e ingresos.
4. **Productos de crédito:** crédito simple configurable, simulador, CAT, comisiones y reglas de mora.
5. **Originación:** solicitud, evaluación, consulta SIC, dictamen y aprobación.
6. **CDC/SIC:** formalizar request data desde cliente real, consentimiento, manejo de errores y resultados normalizados.
7. **PLD:** perfil transaccional, riesgo, alertas, listas, evidencia y reportes.
8. **Amortización y cobranza:** calendario, pagos, mora, cartera vencida y reestructuras.
9. **Crédito revolvente:** líneas, disposiciones, cortes, pagos mínimos y estados de cuenta.
10. **Reportes y contabilidad:** cartera, colocación, cobranza, morosidad, conciliación y exportables.

## Documentación de Trabajo

La investigación y los TODOs viven en:

- `docs/roadmap-financiero.md`
- `docs/todos/00-lecturas-y-fuentes.md`
- `docs/todos/01-configuracion-empresa.md`
- `docs/todos/02-clientes-expediente-kyc.md`
- `docs/todos/03-productos-crediticios.md`
- `docs/todos/04-originacion-solicitudes.md`
- `docs/todos/05-cdc-score-sic.md`
- `docs/todos/06-pld-cumplimiento.md`
- `docs/todos/07-amortizacion-pagos-cobranza.md`
- `docs/todos/08-reportes-contabilidad.md`

## Nota Legal

Esta documentación identifica capacidades que el software debe soportar, pero no sustituye asesoría legal, regulatoria, fiscal ni de PLD. Cada financiera debe validar sus obligaciones concretas con asesores especializados y autoridades aplicables.
