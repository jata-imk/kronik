# Actualizar módulos y capturas de la portada

## Contenido

La portada pública se compone en `resources/js/Pages/Welcome.vue`. El contenido de
productos, documentos, solicitudes, créditos, roles y módulos complementarios está
en `resources/js/Components/Landing/modules.js`. Cada módulo describe su propósito
y tres pasos de uso. Las tarjetas complementarias despliegan esos pasos mediante
**Cómo se usa**.

SIC tiene una sección propia con tres capturas, servicios identificados y estado
**Disponible en simulación**. Describir únicamente capacidades implementadas:
la respuesta determina las secciones disponibles, el score no aprueba créditos y
las operaciones financieras mostradas siguen siendo QA. Evaluación, cumplimiento
y revisión de firma conservan su intervención humana.

## Capturas públicas

1. Iniciar el entorno E2E aislado siguiendo su procedimiento; nunca preparar o
   reseedear una base persistente para obtener imágenes.
2. Usar únicamente fixtures sintéticos. Para SIC, activar el gateway fixture E2E;
   no llamar al proveedor ni utilizar credenciales o reportes de clientes.
3. Tomar capturas con Playwright. SIC utiliza el reporte, el bloque de gráficas de
   cuentas y la tabla histórica; accesos muestra un rol de consulta de ejemplo.
4. Guardarlas en `public/images/landing/` y revisar visualmente cada archivo antes
   de publicarlo. Actualizar `src`, `alt`, título, descripción y dimensiones en el
   catálogo. Estos archivos son públicos y no requieren sesión.

Las capturas originales de los cuatro módulos conservan datos de tablas ocultos;
las nuevas SIC y roles proceden exclusivamente de la base sintética E2E.

## Revisar el resultado

- Comprobar escritorio, móvil, tema claro/oscuro y movimiento reducido.
- Abrir cada imagen, navegar SIC con botones o flechas y cerrar con Escape.
- Probar **Acercar captura** y desplazamiento dentro de la imagen en móvil; al
  cambiar de captura se restablece el ajuste. Cerrar devuelve el foco al disparador.
- Revisar que las imágenes cargan sin sesión, que los pasos se despliegan y que
  la página no se desborda horizontalmente.
- Ejecutar `npm run build` y el escenario `landing-modules.spec.js` mediante el
  lanzador E2E del repositorio. Las pruebas no necesitan SIC externo.

Los estilos de estas secciones y del visor están en
`resources/css/landing-modules.css`; usan los tokens compartidos de Kronik.
El visor conserva foco modal, navegación por teclado y zoom con desplazamiento.

## Referencias visuales

- [SIC en escritorio](../assets/landing-sic-desktop.png).
- [SIC móvil](../assets/landing-sic-mobile.png) y [tema oscuro](../assets/landing-sic-mobile-dark.png).
- [Roles y permisos](../assets/landing-roles-desktop.png).
- [Módulos y pasos de uso en móvil](../assets/landing-modulos-mobile.png).
