# Validar el calendario crediticio en QA

1. Usar una base aislada, permisos de consulta/gestión y una cuenta Super Admin.
   Abrir **Configuración → Calendario crediticio**. Cargar plantilla
   CNBV 2026, guardar el borrador, revisar la cobertura y publicar como Super Admin.
2. Abrir **Ejemplos**. Con disposición 07/11/2026, frecuencia semanal, un periodo
   y gracia de tres días, comprobar que 14/11 pasa a 17/11 por fin de semana y
   feriado del lunes. Verificar que el atraso empezaría el 18 y la mora el 21
   si la obligación sigue impaga.
3. Abrir una versión exacta desde el historial. Revisar leyenda, teclado, foco
   del diálogo, mensajes y presentación en móvil y modo oscuro.
4. Crear una versión de producto con **Siguiente día hábil**. Simular y enviar
   una solicitud sintética. Publicar otra versión con una apertura excepcional
   y verificar que el paquete y el calendario firmado conservan las fechas de
   la solicitud enviada.
5. Usar el [recorrido Bullet](qa-bullet.md) y el de
   [anticipos y liquidación](qa-anticipos-liquidacion.md) para verificar
   movimientos, reversos y proyecciones con las fechas ajustadas.
6. En la bitácora, revisar actor y contexto de guardado y publicación. Registrar
   commit, ambiente, políticas, fechas y resultado en la Agent Note y Notion.

La CI ejecuta tres intentos concurrentes de publicación sobre dos borradores en
MariaDB aislada. Verifica un evento por versión y un orden de publicación único.

[Contrato del calendario](../reference/calendario-crediticio.md)
