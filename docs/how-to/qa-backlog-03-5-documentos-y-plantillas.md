# Guion QA manual — Backlog 03.5

Este guion cubre plantillas, versionado, generación PDF, seguimiento asíncrono,
documentos privados, visor, descargas, permisos, seguridad, auditoría,
responsive y operación del worker.

> [!IMPORTANT]
> Las plantillas, versiones, documentos generados y eventos de auditoría no
> tienen eliminación destructiva. Los datos creados durante QA permanecerán en
> la base y en el almacenamiento privado. Usa exclusivamente información
> sintética.

## 1. Cuentas y datos de prueba

Cuenta principal:

```text
Correo: test@example.com
Contraseña: password
Perfil: Super Admin
```

Perfiles negativos disponibles si fueron creados previamente:

```text
Consulta:
consulta.clientes@example.test
password

Editor:
editor.expedientes@example.test
password

Sin acceso:
sin.acceso.clientes@example.test
password
```

No ejecutes `DevelopmentSeeder` en la VPS para recrearlos. Si alguno no existe,
marca esa comprobación como bloqueada y utiliza un usuario equivalente ya
configurado.

Cliente recomendado:

```text
ana.garcia@example.test
```

Si no existe, utiliza otro cliente claramente sintético. Evita expedientes
reales.

Identificador sugerido para todos los registros:

```text
QA035-20260826-A
```

Si repites el recorrido, cambia la letra final para evitar claves duplicadas.

## 2. Preparar archivos controlados

Crea en tu PC una carpeta llamada `QA-Kronik-03.5` y prepara estos archivos.

### 2.1 PDF válido

En Word, LibreOffice o Google Docs escribe:

```text
DOCUMENTO SINTÉTICO QA035
Este archivo se utiliza exclusivamente para probar el almacenamiento privado.
No contiene información personal real.
```

Expórtalo como `QA035-documento-valido-v1.pdf`. Crea otra copia cambiando el
texto a `DOCUMENTO SINTÉTICO QA035 — VERSIÓN 2` y guárdala como
`QA035-documento-valido-v2.pdf`.

### 2.2 Imágenes válidas

Prepara dos capturas sin información personal:

```text
QA035-imagen.png
QA035-imagen.jpg
```

### 2.3 PDF falso

En Bloc de notas escribe `Esto no es realmente un archivo PDF` y guárdalo,
usando **Todos los archivos**, como `QA035-falso.pdf`.

### 2.4 Contenido activo disfrazado

Crea `QA035-activo.png` con este contenido de texto:

```xml
<svg xmlns="http://www.w3.org/2000/svg">
    <script>alert("QA035")</script>
    <text x="10" y="20">Contenido activo</text>
</svg>
```

El propósito es que tenga extensión PNG, aunque internamente sea SVG.

### 2.5 Archivo mayor a 10 MB, opcional

En PowerShell, dentro de la carpeta:

```powershell
Copy-Item .\QA035-documento-valido-v1.pdf .\QA035-mayor-10mb.pdf

$archivo = [System.IO.File]::OpenWrite(
    (Resolve-Path .\QA035-mayor-10mb.pdf).Path
)
$archivo.SetLength(11MB)
$archivo.Dispose()
```

## 3. Verificación operativa previa

En la VPS, con el usuario `debian`:

```bash
sudo systemctl status kronik-queue.service --no-pager
```

Resultado esperado: `Active: active (running)`.

Comprueba el proceso:

```bash
ps -eo pid,user,nice,rss,args | grep '[a]rtisan queue:work'
```

Debe incluir:

```text
--sleep=5
--tries=3
--timeout=60
--max-jobs=100
--memory=256
```

Comprueba los trabajos fallidos previos:

```bash
cd /home/kronik/htdocs/kronik.josetejero.com
php artisan queue:failed
```

Anota cualquier fallo anterior para no confundirlo con la sesión actual.

## 4. Preparar el navegador

1. Abre Google Chrome en tu PC. No necesitas abrir Chrome for Testing de la
   VPS; ese navegador se usa internamente para renderizar PDF.
2. Abre una ventana de incógnito con `Ctrl + Shift + N`.
3. Navega a `https://kronik.josetejero.com`.
4. Abre DevTools con `F12`.
5. En **Network**, activa `Preserve log` y `Disable cache`.
6. En **Console**, verifica que no existan errores rojos.
7. Mantén el zoom del navegador en 100 %.

## 5. Inicio de sesión y navegación

1. Inicia sesión con `test@example.com` y `password`.
2. Confirma que llegas al dashboard sin errores 419, 403 o 500.
3. Abre **Documentos y plantillas** desde el menú principal.
4. Verifica:
   - encabezado **Documentos y plantillas**;
   - texto **Centro documental**;
   - botón **Nueva plantilla**;
   - métricas Plantillas activas, Borradores en revisión y Documentos
     generados;
   - buscador y filtros Todos, SIC, Garantía y Contrato;
   - ausencia de scroll horizontal accidental.
5. Toma una captura inicial.

## 6. Validaciones del formulario de plantilla

### 6.1 Abrir el editor

1. Presiona **Nueva plantilla**.
2. Verifica que el diálogo muestre:
   - título **Nueva plantilla**;
   - estado **Borrador**;
   - Nombre, Clave, Tipo, Resumen de cambios y Descripción comercial;
   - secciones Encabezado, Contenido y Pie de página;
   - panel **Variables permitidas**;
   - acción **Guardar borrador**;
   - ningún mecanismo para ejecutar PHP, JavaScript o expresiones.

### 6.2 Campos requeridos y clave inválida

1. Vacía **Nombre**.
2. En **Clave** escribe `QA Consentimiento Con Espacios`.
3. Presiona **Guardar borrador**.
4. Deben aparecer mensajes legibles en español.
5. No debe aparecer `validation.required`, `validation.regex` ni otra clave
   interna.

### 6.3 Variable desconocida

Corrige los datos:

```text
Nombre: QA035 Consentimiento SIC A
Clave: qa035-consentimiento-sic-20260826-a
Tipo: Consentimiento SIC
Resumen: Versión inicial QA035-A
Descripción: Plantilla sintética para revisión manual de Backlog 03.5.
```

1. Agrega `{{cliente.cuenta_bancaria}}` al contenido.
2. Intenta guardar.
3. Debe indicarse en español que la variable no existe en el catálogo.
4. Elimina completamente la variable.

### 6.4 Variable incompatible

1. Agrega `{{garantia.descripcion}}` al Consentimiento SIC.
2. Intenta guardar.
3. Debe rechazarse porque la variable no está disponible para ese tipo.
4. Elimínala antes de continuar.

### 6.5 Catálogo visible

Confirma que aparezcan variables generales como fecha de generación, razón
social, nombre comercial, nombre completo, RFC, CURP, teléfono y correo. Las
variables de garantía no deben aparecer para Consentimiento SIC.

1. Confirma que el panel permanezca visible al bajar hasta el editor en escritorio.
2. Busca por nombre, clave y origen; limpia la búsqueda y comprueba los grupos Documento, Empresa y Cliente.
3. Las variables requeridas deben explicar que la generación se impedirá cuando falte el dato en el expediente.

## 7. Crear el borrador SIC

### 7.1 Encabezado

En **Encabezado** escribe:

```text
{{empresa.razon_social}}
Documento QA035-A
```

### 7.2 Contenido

En **Contenido** escribe:

```text
CONSENTIMIENTO SINTÉTICO PARA PRUEBAS

Yo, {{cliente.nombre_completo}}, manifiesto que este documento es una
prueba técnica sin valor jurídico.

RFC de referencia: {{cliente.rfc}}

Este documento fue creado exclusivamente para validar generación,
versionado, visualización y descarga privada en Kronik.
```

Comprueba negrita, cursiva, subrayado, listas numeradas, viñetas y cita. Copia
un párrafo sintético varias veces para forzar al menos dos páginas en el PDF.

### 7.3 Pie de página

En **Pie de página** escribe:

```text
Generado el {{documento.fecha_generacion}} · QA035-A
```

### 7.4 Guardar

1. Presiona **Guardar borrador**.
2. Debe aparecer el Toast **Plantilla creada**.
3. Confirma versión 1, estado Borrador, historial, hash y acciones Editar,
   Duplicar y Activar.
4. Anota o captura los últimos caracteres del hash.

## 8. Editar un borrador

1. Presiona **Editar**.
2. Confirma que Clave y Tipo estén deshabilitados.
3. Cambia el resumen a `Ajuste de redacción y formato QA035-A`.
4. Agrega `MARCADOR DE VERSIÓN: SIC-V1-QA035-A` al contenido.
5. Guarda.
6. Debe aparecer **Borrador guardado**, cambiar el hash y mantenerse una única
   versión Borrador.

## 9. Previsualización de plantilla

1. Presiona **Previsualizar**.
2. Confirma:
   - diálogo **Previsualización de plantilla**;
   - aviso de datos sintéticos y que no es el PDF definitivo;
   - variables resueltas con datos ficticios;
   - ausencia de datos del cliente real;
   - encabezado, cuerpo y pie diferenciados;
   - formato razonable de negritas, listas y citas;
   - ausencia de tokens conocidos sin resolver.
3. Maximiza y restaura el diálogo.
4. Desplázate con el scroll hasta la segunda página y comprueba que el indicador cambie sin usar los botones de página.
5. Ciérralo con `Esc`.

## 10. Activar e inmovilizar la versión 1

1. Presiona **Activar versión**.
2. Confirma que el diálogo explique la inmutabilidad.
3. Presiona primero **Cancelar** y verifica que siga en Borrador.
4. Repite y presiona **Activar**.
5. Debe quedar Activa y mostrar **Versión histórica protegida**.
6. Debe desaparecer **Editar** y permanecer **Duplicar**.
7. Debe aparecer **Retirar versión**.
8. No debe existir una vía visual de edición destructiva.

## 11. Abrir un expediente

1. Abre **Clientes → Listado**.
2. Confirma que **Sucursal actual** aparezca seleccionada y que cada acción muestre tooltip.
3. Busca `ana.garcia@example.test` o selecciona otro cliente sintético.
4. Presiona **Abrir expediente de…**.
5. Confirma el encabezado **Expediente KYC** y las secciones Perfil económico,
   Documentos, Referencias, Vinculados, Garantías y Consentimiento SIC.
6. Abre **Documentos**.
7. Deben aparecer **Generar documento**, **Documento adicional**, Documentos
   del cliente y Documentos finales.

## 12. Carga privada de PDF, JPG y PNG

### 12.1 Cargar PDF requerido

1. Localiza un documento requerido con **Sin archivo recibido**.
2. Presiona **Cargar**.
3. Selecciona `QA035-documento-valido-v1.pdf`.
4. Agrega la nota `Carga sintética QA035-A`.
5. Guarda.
6. Debe quedar Recibido y mostrar Ver, Descargar, Revisar y Sustituir.

### 12.2 Visor PDF privado

1. Presiona **Ver de forma segura**.
2. Confirma **Visor seguro**, etiqueta **Privado** y el texto **Acceso
   autorizado · sin URL pública**.
3. Debe aparecer skeleton durante la carga y después el PDF.
4. En Network, revisa la solicitud `/ver` y estos encabezados:

```text
Content-Type: application/pdf
Cache-Control: private, no-store, max-age=0
X-Content-Type-Options: nosniff
Referrer-Policy: no-referrer
```

5. El visor debe usar una URL temporal `blob:`, no `/storage/...`.
6. Cierra y vuelve a abrir el visor.

### 12.3 Descarga PDF

1. Presiona **Descargar**.
2. El navegador debe descargar el archivo sin navegar a una URL pública.
3. El PDF debe abrir correctamente y tener un nombre seguro.

### 12.4 PNG y JPG

1. Presiona **Documento adicional**.
2. Crea `QA035 Imagen PNG` con `QA035-imagen.png`.
3. Abre el visor y prueba Acercar, Alejar y Girar.
4. Repite como `QA035 Imagen JPG` con `QA035-imagen.jpg`.
5. Ambas imágenes deben visualizarse, rotarse y descargarse sin desbordar la
   interfaz.

## 13. Revisión y versionado de documentos cargados

### 13.1 Validar

1. En el PDF cargado presiona **Revisar**.
2. Selecciona **Validar** y presiona **Aplicar estado**.
3. Debe cambiar a Validado y aumentar el contador correspondiente.

### 13.2 Sustituir

1. Presiona **Sustituir**.
2. Selecciona `QA035-documento-valido-v2.pdf`.
3. Guarda.
4. El actual debe mostrar v2 y un desplegable **Historial (1)** dentro de su propia fila.
5. Al expandirlo deben aparecer estado localizado, icono, fecha, revisor y motivo cuando aplique; la versión anterior debe seguir disponible para ver y descargar.

### 13.3 Rechazo sin motivo

1. Revisa la versión actual y selecciona **Rechazar**.
2. Deja vacío el motivo y aplica.
3. Debe mostrarse:

```text
Indique el motivo por el que se rechaza el documento.
```

4. Escribe `Rechazo sintético QA035: comprobación de validación` y aplica.
5. El motivo debe mostrarse en la tarjeta.

## 14. Generación PDF y seguimiento automático

### 14.1 Solicitar generación

1. Presiona **Generar documento**.
2. Confirma que el diálogo explique la versión activa exacta y que no
   representa una firma.
3. Debe aparecer `QA035 Consentimiento SIC A · v1` y no un Contrato.
4. Selecciona la plantilla y presiona **Generar PDF** una sola vez.
5. Debe aparecer **Generación solicitada**, una fila Pendiente o Procesando y
   la etiqueta animada **Actualizando**.

### 14.2 Comprobar el polling

1. En Network observa solicitudes a `/estado` aproximadamente cada dos
   segundos.
2. No debe haber solicitudes solapadas para el mismo documento.
3. Con `--sleep=5` puede haber dos o tres comprobaciones antes del render.
4. El estado debe cambiar automáticamente a Generado.
5. Deben aparecer Ver y Descargar sin recargar la página.
6. Debe desaparecer **Actualizando**.

### 14.3 Revisar el PDF final

1. Presiona **Ver documento final**.
2. Comprueba razón social, cliente, marcador `SIC-V1-QA035-A`, fecha, A4,
   saltos razonables, encabezado, pie, paginación `1 / N`, acentos y ausencia
   de tokens `{{...}}`.
3. Descarga y conserva el PDF como evidencia de v1.

### 14.4 Duplicados y paginación

1. Intenta generar otra vez la misma versión y contexto.
2. Si la primera sigue pendiente o procesando, la segunda solicitud debe quedar bloqueada.
3. Si ya terminó, debe mostrarse el PDF existente y exigirse **Confirmo que deseo crear otra copia trazable**.
4. Confirma y genera: ambos registros y archivos deben conservarse.
5. Con más de diez registros, comprueba el total y la navegación paginada del más reciente al más antiguo.

## 15. Versionado e inmutabilidad después de uso

1. Regresa al Centro documental y busca la plantilla SIC.
2. Confirma uso registrado de al menos un documento y ausencia de Editar.
3. Presiona **Duplicar** y confirma **Crear borrador**.
4. El historial debe contener v1 Activa y v2 Borrador.
5. Edita v2 y agrega:

```text
Resumen: Segunda versión QA035-A
MARCADOR DE VERSIÓN: SIC-V2-QA035-A
```

6. Guarda, previsualiza y activa v2.
7. v2 debe quedar Activa y v1 Retirada.
8. Ambas deben conservar historial y hashes distintos.
9. Ninguna versión histórica debe permitir edición.

## 16. Reproducibilidad entre versiones

1. Vuelve al expediente y abre Generar documento.
2. Debe aparecer v2, pero no v1 retirada.
3. Genera v2 y espera al polling.
4. Abre ambos documentos finales.
5. El antiguo debe conservar `SIC-V1-QA035-A` y el nuevo mostrar
   `SIC-V2-QA035-A`.
6. Publicar v2 no debe modificar el PDF v1.

## 17. Plantilla y generación de garantía

### 17.1 Garantía sintética

1. En el expediente abre **Garantías**.
2. Si no existe una garantía sintética, crea:

```text
Tipo: Prendaria
Descripción: Vehículo sintético QA035-A
Valor estimado: 25000
Moneda: MXN
Notas: Garantía controlada para pruebas documentales.
```

### 17.2 Plantilla

Crea:

```text
Nombre: QA035 Garantía A
Clave: qa035-garantia-20260826-a
Tipo: Garantía
Resumen: Versión inicial QA035 garantía
Descripción: Constancia sintética de garantía para QA.
```

Contenido:

```text
CONSTANCIA SINTÉTICA DE GARANTÍA

Cliente: {{cliente.nombre_completo}}
Tipo: {{garantia.tipo}}
Descripción: {{garantia.descripcion}}
Valor: {{garantia.valor_estimado}}
Moneda: {{garantia.moneda}}
Propietario: {{garantia.propietario}}

MARCADOR: GARANTIA-V1-QA035-A
```

1. Comprueba que el catálogo muestre variables de garantía.
2. Guarda, previsualiza y activa.
3. En el expediente selecciona la plantilla de garantía.
4. Debe aparecer un bloque obligatorio **Garantía**.
5. Generar PDF debe permanecer deshabilitado hasta seleccionar una garantía.
6. Selecciona `Vehículo sintético QA035-A`, genera y valida el PDF.

## 18. Contrato y separación de alcance

Crea y activa:

```text
Nombre: QA035 Contrato A
Clave: qa035-contrato-20260826-a
Tipo: Contrato
Resumen: Preparación arquitectónica QA035
Descripción: Contrato sintético; generación reservada para originación.
```

Contenido:

```text
CONTRATO SINTÉTICO SIN VALOR JURÍDICO

Preparado para {{cliente.nombre_completo}}.

MARCADOR: CONTRATO-QA035-A
```

Comprueba que puede administrarse, versionarse y previsualizarse, pero no
aparece en Generar documento. No debe existir firma real, DOCX ni originación
implementada por este backlog.

## 19. Retiro de versiones

1. Selecciona una versión QA activa y presiona **Retirar versión**.
2. Cancela la primera confirmación y comprueba que siga activa.
3. Repite y confirma **Retirar**.
4. Debe quedar Retirada, conservar historial y desaparecer de nuevas
   generaciones.
5. Los PDF anteriores deben seguir disponibles.

Al terminar, retira las demás plantillas QA que no deban seguir disponibles.

## 20. Seguridad de carga

### 20.1 PDF falso

Intenta subir `QA035-falso.pdf`. Debe rechazarse con:

```text
El archivo debe ser un PDF, JPG o PNG válido; su contenido no coincide con la extensión.
```

### 20.2 SVG activo disfrazado

Intenta subir `QA035-activo.png`. Debe rechazarse sin ejecutar ningún `alert`
ni abrirse como contenido confiable.

### 20.3 Archivo demasiado grande

Intenta subir `QA035-mayor-10mb.pdf`. Debe bloquearse en frontend o backend con
un mensaje legible, sin error 500 ni registro utilizable.

### 20.4 Extensión no permitida

Intenta seleccionar `.html`, `.svg` o `.exe`. El selector debe filtrarlos y el
servidor debe rechazarlos si llegan a enviarse.

## 21. URLs privadas y manipulación de identificadores

1. En DevTools busca `storage/app`: no debe aparecer en el DOM o las respuestas
   públicas.
2. No debe aparecer ninguna ruta `/home/kronik/htdocs/...`.
3. El visor debe usar una URL temporal `blob:`.
4. Copia una URL `/clientes/{cliente}/documentos/{documento}/ver`.
5. Cambia sólo el ID del cliente: debe responder 404.
6. Cambia el documento por un ID inexistente: debe responder 404, nunca 500.
7. Prueba segmentos codificados como `%2E%2E`: deben rechazarse sin entregar
   archivos.

## 22. Pruebas de permisos

Antes de cerrar Super Admin, copia las URL del Centro documental, expediente,
vista y descarga de un PDF generado.

### 22.1 Usuario de consulta

Con `consulta.clientes@example.test`:

1. Puede abrir clientes y expediente.
2. No aparece Documentos y plantillas.
3. `/plantillas-documentos` responde 403.
4. No aparece Generar documento.
5. No puede modificar el expediente.
6. Las URL de vista y descarga de un PDF generado responden 403.

### 22.2 Editor de expedientes

Con `editor.expedientes@example.test`:

1. Puede editar el expediente y cargar/revisar documentos del cliente.
2. Puede administrar garantías.
3. No aparece Generar documento.
4. No puede abrir el Centro documental ni un PDF generado sin permisos.

### 22.3 Usuario sin acceso

Con `sin.acceso.clientes@example.test`:

1. No puede abrir el expediente.
2. Las URL directas responden 403.
3. No puede acceder a plantillas, vista ni descarga.

Antes de producción, estas credenciales demo deben cambiarse, desactivarse o
eliminarse según la política operativa.

## 23. Auditoría

1. Como Super Admin abre **Administración → Logs de Actividades**.
2. Filtra sucesivamente por:
   - Plantilla documental creada.
   - Borrador de plantilla actualizado.
   - Plantilla documental versionada.
   - Versión de plantilla activada.
   - Versión de plantilla retirada.
   - Generación de documento solicitada.
   - Documento generado.
   - Documento visualizado.
   - Documento descargado.
   - Documento de cliente recibido.
   - Estado documental actualizado.
   - Documento de cliente visualizado.
   - Documento de cliente descargado.
3. Confirma usuario, fecha, equipo, sucursal, descripción y sujeto.
4. Abre **Propiedades**.
5. Sólo deben aparecer metadatos mínimos como `changed_fields`, `related.type`,
   `related.id`, `state` o `version`.
6. No deben aparecer HTML completo, PDF, path físico, RFC, CURP, contraseñas,
   tokens ni payloads sensibles completos.

## 24. Timeout controlado del polling

> [!CAUTION]
> Esta prueba detiene temporalmente el worker de Kronik. Ejecútala únicamente
> cuando no haya otros trabajos importantes pendientes.

### 24.1 Detener

```bash
sudo systemctl stop kronik-queue.service
sudo systemctl status kronik-queue.service --no-pager
```

### 24.2 Solicitar y esperar

1. Con una plantilla QA activa, solicita una generación.
2. Debe quedar Pendiente y mostrar **Actualizando**.
3. Espera 90 segundos sin recargar.
4. Debe aparecer **Seguimiento pausado**, un panel amarillo y **Actualizar
   estado**.
5. El navegador debe dejar de hacer consultas periódicas.
6. El job no debe cancelarse ni duplicarse.

### 24.3 Recuperar

```bash
sudo systemctl start kronik-queue.service
sudo systemctl status kronik-queue.service --no-pager
```

1. Espera entre 5 y 15 segundos.
2. Presiona **Actualizar estado**.
3. Debe reanudar el seguimiento si aún procesa y terminar en Generado.
4. No debe crearse un segundo registro o PDF.
5. Comprueba `php artisan queue:failed`.

## 25. Responsive móvil

En Chrome activa `Ctrl + Shift + M` y configura `390 × 844`.

### 25.1 Catálogo

Verifica título, Nueva plantilla, métricas, buscador, filtros, historial,
acciones, editor y panel de variables. No debe existir scroll horizontal global.

### 25.2 Expediente

Verifica navegación, tarjetas, acciones, diálogo Generar documento, polling,
timeout e historial.

### 25.3 Visor

Abre PDF y PNG. Debe ocupar correctamente la pantalla, ser desplazable,
cerrarse y mantener accesibles descarga, zoom y rotación.

## 26. Accesibilidad y teclado

1. Repite parte del recorrido usando `Tab`, `Enter`, `Espacio` y `Esc`.
2. Confirma foco visible en buscador, filtros, catálogo, historial, acciones,
   vista y descarga.
3. Los botones con sólo icono deben tener nombre accesible.
4. Los estados deben mostrar texto además de color.
5. A 200 % de zoom las acciones deben seguir alcanzables.
6. No deben aparecer `alert()` o `confirm()` nativos.

## 27. Revisión visual general

Registra como defecto cualquiera de estos casos:

- botones o textos cortados destructivamente;
- elementos superpuestos;
- nombres largos sin ellipsis;
- scroll horizontal global;
- Toast debajo de un diálogo;
- ConfirmDialog sin acciones visibles;
- estados distinguibles únicamente por color;
- visor sin indicador de carga;
- botones principales fuera del viewport;
- errores en inglés o claves de traducción;
- errores rojos en Console;
- respuestas 500 en Network.

## 28. Comprobación final en la VPS

Con `kronik`:

```bash
cd /home/kronik/htdocs/kronik.josetejero.com

php artisan queue:failed

php artisan tinker --execute='
$documento = App\Models\DocumentoGenerado::latest("solicitado_en")->first();
dump($documento?->only([
    "id",
    "documento_plantilla_version_id",
    "cliente_id",
    "estado",
    "nombre_archivo",
    "mime_type",
    "tamano_bytes",
    "archivo_hash",
    "solicitado_en",
    "generado_en",
]));
'
```

Debe mostrar estado `generado`, MIME `application/pdf`, tamaño mayor que cero,
hash, versión exacta y fechas.

Comprueba integridad sin imprimir contenido:

```bash
php artisan tinker --execute='
$documento = App\Models\DocumentoGenerado::latest("solicitado_en")->firstOrFail();
$existe = Illuminate\Support\Facades\Storage::disk($documento->disk)
    ->exists($documento->path);
$hashValido = $existe
    && hash_file(
        "sha256",
        Illuminate\Support\Facades\Storage::disk($documento->disk)
            ->path($documento->path)
    ) === $documento->archivo_hash;
dump([
    "archivo_existe" => $existe,
    "hash_valido" => $hashValido,
]);
'
```

Esperado:

```text
archivo_existe => true
hash_valido => true
```

Comprueba el servicio:

```bash
sudo systemctl show kronik-queue.service \
    -p ActiveState \
    -p SubState \
    -p MainPID \
    -p NRestarts
```

Esperado: `active`, `running`, PID distinto de cero y `NRestarts` estable.

## 29. Cierre de la sesión QA

1. Retira las versiones QA que no deban seguir disponibles.
2. No elimines registros directamente de la base.
3. Conserva:
   - captura del catálogo;
   - historial v1/v2;
   - visor;
   - PDF v1 y v2;
   - PDF de garantía;
   - polling y timeout;
   - un 403;
   - auditoría.
4. Revisa nuevamente Console y Network.
5. Confirma que el worker esté activo y no haya jobs fallidos.
6. Cierra todas las sesiones de prueba.

## 30. Criterio de aprobación

La feature puede aprobarse cuando:

- las plantillas se crean, editan, versionan, activan y retiran;
- una versión activa o utilizada no admite edición destructiva;
- los PDF antiguos permanecen iguales tras activar nuevas versiones;
- la garantía queda asociada al documento correcto;
- Contrato permanece preparado pero fuera de generación;
- el polling actualiza automáticamente y termina a los 90 segundos;
- visor y descarga comprueban autorización;
- PDF, JPG y PNG válidos funcionan;
- contenido falso, activo o sobredimensionado se rechaza;
- no se exponen URLs públicas ni paths físicos;
- permisos y respuestas 403 funcionan;
- auditoría no almacena payloads sensibles;
- escritorio, móvil y teclado funcionan;
- no aparecen errores 500, 419, claves de traducción ni errores de consola.

## 31. Registro de resultados

Por cada incidencia anota:

```text
ID:
Sección del guion:
Resultado: Aprobado / Falló / Bloqueado
Usuario:
Navegador y resolución:
Pasos exactos:
Resultado observado:
Resultado esperado:
Captura o video:
Errores de Console:
Solicitud y respuesta de Network:
Fecha y hora:
```
