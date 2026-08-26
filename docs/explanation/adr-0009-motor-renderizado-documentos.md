# ADR 0009: motor de renderizado de documentos

- Estado: aceptado
- Fecha: 2026-08-23

## Contexto

El primer formato final requerido es PDF. Debe soportar CSS, encabezados, pies, numeración, fuentes e imágenes de confianza en Windows y Linux, sin ejecutar contenido aportado por usuarios. DOCX solo se incorporará cuando exista un caso funcional confirmado.

## Alternativas evaluadas

| Motor | Fidelidad y paginado | Operación | Mantenimiento y licencia | Resultado |
| --- | --- | --- | --- | --- |
| Chromium/Puppeteer mediante Browsershot | CSS moderno, fuentes, encabezado, pie y numeración | Node 22 y Chrome; proceso por generación | Activo; licencias permisivas por componente | Candidato principal |
| Gotenberg | Chromium y LibreOffice detrás de API aislada | Docker, servicio adicional y al menos 512 MB recomendados | Activo; MIT | Alternativa si la VPS adopta contenedores |
| WeasyPrint | Muy buen CSS paginado | Python, Pango y Cairo | Activo; BSD | Fallback sin Chromium |
| Pandoc | Adecuado para DOCX y conversiones semánticas | Requiere motor PDF adicional | Activo; GPL | Reservado para un DOCX futuro |
| Dompdf | CSS 2.1 parcial | PHP puro, despliegue simple | Activo; LGPL | No alcanza la fidelidad objetivo |
| wkhtmltopdf | WebKit antiguo | Binario adicional | Repositorio archivado; LGPL | Descartado |

## Decisión

Usar Chromium/Puppeteer mediante Browsershot detrás del contrato `DocumentoPdfRenderer`. Las generaciones finales se ejecutan en cola y DOCX no forma parte del alcance actual.

El benchmark local en Windows 11, Node 22, Puppeteer 25.8.0 y Browsershot 4.4.0 usó tres ejecuciones por fixture sintético:

| Fixture | Mínimo | Mediana | p95 observado | PDF promedio |
| --- | ---: | ---: | ---: | ---: |
| Consentimiento SIC | 1,656 ms | 1,679 ms | 1,679 ms | 37.1 KiB |
| Garantía | 1,647 ms | 1,661 ms | 1,661 ms | 39.0 KiB |
| Contrato multipágina | 1,661 ms | 1,687 ms | 1,687 ms | 50.3 KiB |

La memoria pico del proceso PHP fue 30 MiB. Las salidas conservaron encabezado, pie, numeración y saltos de página, y quedaron por debajo del umbral de 10 segundos. El RSS de Chromium debe medirse nuevamente en la VPS antes de fijar la concurrencia del worker; no cambia la elección del adaptador.

El HTML se sanea antes de persistir, las variables se escapan, no se permiten imágenes o enlaces aportados por el editor y Chromium recibe reglas para impedir resolución de destinos externos. El job tiene timeout inferior al `retry_after`, exclusión por documento e idempotencia en base de datos.

DOCX no se simula convirtiendo el mismo HTML. Si aparece el requisito se escribirá otro adaptador y se evaluará Pandoc con `reference.docx`.

## Consecuencias

- La VPS debe instalar Node 22, Puppeteer/Chromium y sus bibliotecas del sistema; no se agrega Docker.
- Las pruebas de dominio usan un renderer falso y determinístico; una prueba de humo separada valida el binario real.
- Gotenberg puede sustituir el adaptador sin cambiar plantillas, generaciones ni UI.
- El comando `php artisan documentos:benchmark-pdf --runs=5` sirve como prueba de humo y línea base de capacidad en cada entorno.
