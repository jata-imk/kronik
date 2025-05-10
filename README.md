# Sistema ERP Financiero

[![Formatted with Biome](https://img.shields.io/badge/Formatted_with-Biome-60a5fa?style=flat&logo=biome)](https://biomejs.dev/)
[![Linted with Biome](https://img.shields.io/badge/Linted_with-Biome-60a5fa?style=flat&logo=biome)](https://biomejs.dev)

[![MariaDB Badge](https://img.shields.io/badge/MariaDB-003545?style=for-the-badge&logo=mariadb&logoColor=white)](https://mariadb.org/)
[![Apache Badge](https://img.shields.io/badge/Apache-D22128?style=for-the-badge&logo=Apache&logoColor=white)](https://httpd.apache.org/)
[![Nginx Badge](https://img.shields.io/badge/Nginx-009639?style=for-the-badge&logo=nginx&logoColor=white)](https://nginx.org/)
[![PHP Badge](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/manual/es/intro-whatis.php)
[![Composer Badge](https://img.shields.io/badge/Composer-885630?style=for-the-badge&logo=Composer&logoColor=white)](https://getcomposer.org/)

[![Laravel Badge](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com/docs/11.x/)

[![ChartsJS Badge](https://img.shields.io/badge/Chart%20js-FF6384?style=for-the-badge&logo=chartdotjs&logoColor=white)](https://www.chartjs.org/)
[![Tailwind Badge](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)
[![Vite Badge](https://img.shields.io/badge/Vite-B73BFE?style=for-the-badge&logo=vite&logoColor=FFD62E)](https://vite.dev/)
[![Vue JS Badge](https://img.shields.io/badge/Vue%20js-35495E?style=for-the-badge&logo=vuedotjs&logoColor=4FC08D)](https://vuejs.org/)

[![Jira Badge](https://img.shields.io/badge/Jira-0052CC?style=for-the-badge&logo=Jira&logoColor=white)](https://www.atlassian.com/es/software/jira)

## Descripción

Sistema ERP especializado en la gestión financiera integral, diseñado para instituciones financieras, cooperativas de crédito y empresas que ofrecen servicios financieros. Nuestra plataforma proporciona una solución completa para la administración de productos crediticios, análisis de riesgo, cumplimiento normativo y gestión de clientes.

## Funcionalidades Principales

### Gestión de Productos Financieros
- Creación y administración de productos de crédito (tarjetas, préstamos personales, hipotecarios)
- Configuración personalizada de tasas, plazos y condiciones
- Simuladores de crédito para clientes potenciales

### Gestión de Clientes
- Expediente digital completo de clientes
- Historial crediticio y transaccional
- Gestión de líneas de crédito asignadas
- Portal de autoservicio para clientes

### Análisis de Riesgo
- Evaluación de score crediticio
- Análisis de capacidad de pago (ingresos, egresos)
- Estudio de capital y garantías
- Modelos predictivos de riesgo de impago
- Análisis de condiciones de mercado

### Cumplimiento Normativo
- Prevención de Lavado de Dinero (PLD)
- Know Your Customer (KYC)
- Generación de reportes regulatorios
- Auditoría y trazabilidad de operaciones

### Facturación y Contabilidad
- Facturación electrónica integrada
- Gestión contable de operaciones crediticias
- Reportes financieros (balance general, estado de resultados)
- Integración con SAT y autoridades fiscales

### Cobranza y Recuperación
- Gestión de cartera vencida
- Estrategias automatizadas de cobranza
- Reestructuración de créditos
- Indicadores de desempeño de cobranza

## Stack Tecnológico

### Infraestructura
- **Sistema Operativo**: AlmaLinux 9
- **Base de Datos**: MariaDB
- **Servidor Web**: Nginx/Apache (vía PLESK)
- **Email**: Postfix + Dovecot
- **Panel de Control**: PLESK (con todas sus herramientas integradas)
- **Seguridad**: Certificados SSL, Firewall, Backup automatizado

### Backend
- **Framework**: Laravel (PHP 8)
- **API**: RESTful con autenticación OAuth2/JWT
- **Seguridad**: Encriptación avanzada para datos sensibles
- **Colas**: Laravel Queues para procesamiento asíncrono

### Frontend
- **Framework**: Vue.js con Inertia.js (Jetstream)
- **Build Tool**: Vite
- **CSS**: Tailwind CSS
- **Componentes**: PrimeVue
- **Gráficos**: Chart.js para visualización de datos
- **Responsive**: Diseño adaptable a múltiples dispositivos

## Principios de Desarrollo

- **Arquitectura**: Patrón MVC con servicios y repositorios
- **Código**: Siguiendo PSR-12 y convenciones de Laravel
- **Testing**: PHPUnit para pruebas unitarias y de integración
- **CI/CD**: Pipelines automatizados para despliegue continuo
- **Versionado**: Git con estrategia de ramas por características
- **Documentación**: API documentada con OpenAPI/Swagger

## Seguridad y Cumplimiento

- Cifrado de extremo a extremo para información sensible
- Autenticación multifactor para usuarios administrativos
- Registro detallado de auditoría para todas las operaciones
- Cumplimiento con regulaciones financieras aplicables
- Protección avanzada contra vulnerabilidades OWASP
- Gestión de sesiones y permisos granulares

## Requisitos del Sistema

- PHP 8.1 o superior
- MariaDB 10.5 o superior
- Node.js 18+ (para construcción de frontend)
- Servidor con mínimo 4GB RAM y 2 CPUs

## Instalación y Configuración

1. Clonar el repositorio
2. Configurar variables de entorno en `.env`
3. Instalar dependencias de PHP: `composer install`
4. Instalar dependencias de JavaScript: `npm install`
5. Compilar assets: `npm run build`
6. Ejecutar migraciones: `php artisan migrate`
7. Inicializar datos base: `php artisan db:seed`
8. Instalar en el SO los programas
    8.1 Libreoffice: https://es.libreoffice.org/descarga/libreoffice/
    8.2 wkhtmltopdf https://wkhtmltopdf.org/downloads.html
    8.3 Pandoc: https://pandoc.org/installing.html
9. Configurar servicios externos (email, APIs de terceros)

## Roadmap

- Integración con pasarelas de pago
- Módulo de banca digital
- App móvil para clientes
- Inteligencia artificial para análisis predictivo
- Integración con blockchain para contratos inteligentes
- Expansión internacional con soporte multi-divisa y multi-idioma