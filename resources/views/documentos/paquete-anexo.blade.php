<section style="page-break-before: always; font-size: 9pt">
    <h1>Anexo informativo · Solicitud SOL-{{ $snapshot['solicitud_id'] }}</h1>
    <p><strong>Documento de prueba QA — sin validez contractual.</strong></p>
    @php($conImpuestos = ($snapshot['fiscalidad'] ?? null) === 'proyeccion')
    @if ($conImpuestos)
        <p>Proyección fiscal QA congelada. {{ $snapshot['tabla']['fiscalidad']['leyenda'] }} No autoriza firma, desembolso ni cobro.</p>
        <p>{{ $snapshot['tabla']['fiscalidad']['uso'] === 'prueba' ? 'Configuración fiscal de prueba / QA.' : 'Configuración declarada por la institución; no certificada por el sistema.' }}</p>
    @else
        <p>Fiscalidad no definida. Los importes siguientes son anteriores a impuestos; esto no significa exención ni tasa cero. No autoriza firma, desembolso ni cobro.</p>
    @endif
    <p>Condiciones y simulación conservadas de la revisión {{ $snapshot['revision_id'] }}. La disposición indicada es una fecha estimada, no un movimiento realizado.</p>
    <p>Monto: ${{ number_format((float) $snapshot['tabla']['monto'], 2) }} MXN · {{ $snapshot['tabla']['plazo'] }} pagos · {{ $snapshot['tabla']['periodicidad'] }} · {{ $snapshot['tabla']['metodo'] }}</p>
    <p>Destino: {{ $snapshot['condiciones']['destino'] }}</p>
    <p>Producto versión {{ $snapshot['producto']['numero'] }} · Tasa ordinaria anual: {{ $snapshot['producto']['tasa_ordinaria_anual'] }}% · Tasa moratoria anual: {{ $snapshot['producto']['tasa_moratoria_anual'] }}% · Días de gracia configurados: {{ $snapshot['producto']['dias_gracia_mora'] }}.</p>
    <p>Convención: {{ $snapshot['producto']['reglas']['convencion_interes'] }}. Esta tabla no calcula mora ni aplica las nuevas modalidades de gracia o coexistencia de intereses, pendientes de implementación.</p>
    <p>Saldo financiado: ${{ number_format((float) $snapshot['tabla']['escenario']['saldo_financiado'], 2) }} · Efectivo estimado al cliente: ${{ number_format((float) $snapshot['tabla']['escenario']['efectivo_entregado'], 2) }} MXN.</p>
    <table style="width:100%; border-collapse:collapse; font-size:8pt">
        <thead style="display:table-header-group"><tr>
            @foreach (['Pago', 'Fecha estimada', 'Capital', 'Interés', 'Comisiones', ...($conImpuestos ? ['Impuestos', 'Total con impuestos'] : ['Total sin impuestos']), 'Saldo'] as $titulo)
                <th style="border:1px solid #ccc; padding:5px">{{ $titulo }}</th>
            @endforeach
        </tr></thead>
        <tbody>
        @foreach ($snapshot['tabla']['tabla'] as $fila)
            <tr style="page-break-inside:avoid">
                <td style="padding:5px; border-bottom:1px solid #ddd">{{ $fila['numero'] === 0 ? 'Disposición estimada' : $fila['numero'] }}</td>
                <td>{{ $fila['fecha'] }}</td>
                @foreach (['capital', 'interes', 'comisiones', ...($conImpuestos ? ['impuestos'] : []), 'pago_total', 'saldo_final'] as $campo)
                    <td style="text-align:right">${{ number_format((float) $fila[$campo], 2) }}</td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
    <p>Intereses: ${{ number_format((float) $snapshot['tabla']['total_intereses'], 2) }} · Comisiones: ${{ number_format((float) $snapshot['tabla']['total_comisiones'], 2) }} · Obligaciones {{ $conImpuestos ? 'con impuestos' : 'sin impuestos' }}: ${{ number_format((float) $snapshot['tabla']['total_pagar'], 2) }} MXN.</p>
    @if ($conImpuestos)
        <p>Impuestos proyectados: ${{ number_format((float) $snapshot['tabla']['total_impuestos'], 2) }} MXN. Los impuestos financiados se recuperan dentro del capital, no se suman otra vez a cada cuota. El CAT base conserva su escenario anterior a impuestos.</p>
        <p>Impuestos iniciales: pago separado ${{ number_format((float) $snapshot['tabla']['totales']['impuestos_pago_separado'], 2) }} · retenidos del desembolso ${{ number_format((float) $snapshot['tabla']['totales']['impuestos_retenidos'], 2) }} · financiados ${{ number_format((float) $snapshot['tabla']['totales']['impuestos_financiados'], 2) }} MXN.</p>
        <h2>Desglose fiscal por periodo y concepto</h2>
        @foreach ($snapshot['tabla']['tabla'] as $fila)
            @foreach ($fila['impuestos_detalle'] as $detalle)
                <p style="page-break-inside:avoid">{{ $fila['numero'] === 0 ? 'Disposición' : 'Pago '.$fila['numero'] }} · {{ $detalle['concepto'] }} · {{ ['gravado' => 'Gravado', 'exento' => 'Exento', 'no_causa' => 'No causa impuesto'][$detalle['tratamiento']] }}
                    @if ($detalle['tratamiento'] === 'gravado')
                        · Base: importe del concepto ${{ number_format((float) $detalle['importe_concepto'], 2) }} × {{ $detalle['tasa'] }}%
                    @endif
                    · Impuesto: ${{ number_format((float) $detalle['impuesto'], 2) }} MXN.</p>
            @endforeach
        @endforeach
    @endif
    <p>La fila de disposición puede incluir comisiones iniciales retenidas o financiadas que no representan pagos separados. Este anexo no es un estado de cuenta.</p>
    <p>Huella de la revisión: {{ $snapshot['revision_hash'] }}</p>
</section>
