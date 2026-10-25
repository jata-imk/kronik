# Probar Círculo de Crédito en simulación

## Preparar QA

1. Respaldar base y archivos privados. Aplicar migraciones con `php artisan migrate`.
   No usar `migrate:fresh` en una instalación existente.
2. Configurar `SIC_QA_HABILITADO=true`, `CIRCULO_CREDITO_SANDBOX=true`, host
   `https://services.circulodecredito.com.mx` y API key sandbox autorizada en `.env`.
3. Si PHP carece de autoridades confiables, configurar `SIC_CA_BUNDLE` con un
   archivo confiable. Nunca desactivar verificación TLS.
4. Limpiar/reconstruir configuración y reiniciar workers. Mantener worker y
   scheduler activos. Asignar permisos a operadores: la migración no concede
   conciliación automáticamente a todos los roles.

## Consultar y revisar

1. Cliente sintético de la sucursal actual con nombre, nacimiento y domicilio
   mexicano completos, incluidos municipio y colonia. FICO v2 y RC + FICO requieren
   además ciudad en el [catálogo postal](../reference/sic-estado-integracion.md#acceso-y-evidencia).
   Consentimiento vigente y
   evidencia privada en expediente.
2. **Consultas SIC → nueva consulta**: servicio, consentimiento y revisión vigente
   si será evidencia de originación. Confirmar sandbox.
3. Revisar estado, servicio, procedencia, fecha, scores y razones.
4. RC + FICO agrega gráficas según secciones presentes. Revisar monedas separadas,
   cobertura, fechas de actualización y detalle completo. Cada gráfica tiene
   tabla alternativa. No se asignan rangos ni categorías de riesgo.
5. Un intento incierto no se reenvía. Revisar con proveedor y conciliar con permiso,
   referencia y motivo; después se puede preparar una consulta nueva.

Una respuesta «No se encontró a la persona» del sandbox no fabrica un score ni
acredita por sí sola una política que lo exige. Para documentar un resultado
positivo de simulación, usar un caso sintético publicado por el proveedor y
mantener su identidad, consentimiento y revisión vinculados; no sustituir solo
el cuerpo enviado ni reutilizar evidencia de otra persona.

Conectividad con casos sintéticos oficiales, sin crear evidencia de aprobación:

```powershell
php artisan sic:probar-sandbox fico_score_v2 --confirmar-qa
php artisan sic:probar-sandbox fintech --confirmar-qa
php artisan sic:probar-sandbox rc_fico_score --confirmar-qa
```

Las tres pruebas explícitas del 10/10/2026 devolvieron HTTP 200. Los comandos no
imprimen respuesta/key; las pruebas automatizadas usan fakes.

## Cifrar histórico

Con respaldo recuperable y en mantenimiento:

```powershell
php artisan sic:cifrar-historico --dry-run
php artisan sic:cifrar-historico
php artisan sic:cifrar-historico --retirar-texto-plano
```

El último paso verifica lectura cifrada, hash y equivalencia antes de retirar
JSON plano. Conserva registros/IDs. No consulta al proveedor ni acredita
históricos para aprobación. Conservar de forma segura la clave Laravel y respaldo.

Capturas **demostrativas aisladas**:
[escritorio](../assets/sic-qa-reporte-desktop.png),
[móvil](../assets/sic-qa-reporte-mobile.png),
[tema oscuro](../assets/sic-qa-reporte-dark.png).
