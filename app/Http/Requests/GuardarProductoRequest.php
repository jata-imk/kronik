<?php

namespace App\Http\Requests;

use App\Models\ProductoCrediticio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $producto = $this->route('producto');

        return [
            'clave' => ['required', 'string', 'max:40', Rule::unique('productos_crediticios', 'clave')->ignore($producto instanceof ProductoCrediticio ? $producto->id : null)],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'version' => ['required', 'array'],
            'version.monto_minimo' => ['required', 'decimal:0,4', 'gt:0'],
            'version.monto_maximo' => ['required', 'decimal:0,4', 'gte:version.monto_minimo'],
            'version.tasa_ordinaria_anual' => ['required', 'decimal:0,8', 'min:0', 'max:1000'],
            'version.tasa_moratoria_anual' => ['required', 'decimal:0,8', 'min:0', 'max:1000'],
            'version.dias_gracia_mora' => ['required', 'integer', 'min:0', 'max:365'],
            'version.cat_aplica' => ['required', 'boolean'],
            'version.cat_no_aplica_motivo' => ['nullable', 'required_if:version.cat_aplica,false', 'string', 'max:255'],
            'version.vigente_desde' => ['nullable', 'date'],
            'version.periodicidades' => ['required', 'array', 'min:1'],
            'version.periodicidades.*.periodicidad' => ['required', Rule::in(['semanal', 'quincenal', 'mensual']), 'distinct'],
            'version.periodicidades.*.plazo_minimo' => ['required', 'integer', 'min:1', 'max:600'],
            'version.periodicidades.*.plazo_maximo' => ['required', 'integer', 'gte:version.periodicidades.*.plazo_minimo', 'max:600'],
            'version.periodicidades.*.plazo_predeterminado' => ['required', 'integer', 'min:1', 'max:600'],
            'version.reglas' => ['required', 'array'],
            'version.reglas.metodos_amortizacion' => ['required', 'array', 'min:1'],
            'version.reglas.metodos_amortizacion.*' => ['required', Rule::in(['cuota_nivelada', 'capital_fijo']), 'distinct'],
            'version.reglas.permite_prepago_parcial' => ['required', 'boolean'],
            'version.reglas.permite_liquidacion_anticipada' => ['required', 'boolean'],
            'version.reglas.monto_minimo_prepago' => ['nullable', 'decimal:0,4', 'min:0'],
            'version.reglas.aplicacion_prepago' => ['required', Rule::in(['reducir_plazo', 'reducir_pago'])],
            'version.comisiones' => ['present', 'array'],
            'version.comisiones.*.concepto_comision_id' => ['required', 'integer', 'distinct', Rule::exists('conceptos_comision', 'id')->where('activo', true)],
            'version.comisiones.*.tipo_importe' => ['required', Rule::in(['fijo', 'porcentaje'])],
            'version.comisiones.*.importe' => ['required', 'decimal:0,8', 'min:0'],
            'version.comisiones.*.base_calculo' => ['required', Rule::in(['no_aplica', 'monto_credito'])],
            'version.comisiones.*.momento_cobro' => ['required', Rule::in(['inicio', 'firma', 'desembolso_descuento', 'cada_pago', 'evento', 'liquidacion'])],
            'version.comisiones.*.modalidad_cobro' => ['nullable', Rule::in(['pago_separado', 'descuento_desembolso', 'financiada'])],
            'version.comisiones.*.obligatoria' => ['required', 'boolean'],
            'version.comisiones.*.incluye_cat' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ($this->input('version.periodicidades', []) as $index => $periodicidad) {
                $minimo = (int) ($periodicidad['plazo_minimo'] ?? 0);
                $maximo = (int) ($periodicidad['plazo_maximo'] ?? 0);
                $predeterminado = (int) ($periodicidad['plazo_predeterminado'] ?? 0);
                if ($predeterminado < $minimo || $predeterminado > $maximo) {
                    $validator->errors()->add("version.periodicidades.$index.plazo_predeterminado", 'El plazo predeterminado debe estar dentro del rango permitido.');
                }
            }
            $tasaMora = (string) $this->input('version.tasa_moratoria_anual', '0');
            foreach ($this->input('version.comisiones', []) as $index => $comision) {
                $concepto = \App\Models\ConceptoComision::find($comision['concepto_comision_id'] ?? null);
                if ($concepto?->clave === 'PAGO_TARDIO' && bccomp($tasaMora, '0', 8) > 0) {
                    $validator->errors()->add("version.comisiones.$index.concepto_comision_id", 'No puede cobrarse comisión por pago tardío y tasa moratoria en el mismo periodo.');
                }
                if (($comision['tipo_importe'] ?? null) === 'porcentaje' && ($comision['base_calculo'] ?? null) !== 'monto_credito') {
                    $validator->errors()->add("version.comisiones.$index.base_calculo", 'Seleccione el monto del crédito como base de una comisión porcentual.');
                }
                $inicial = in_array($comision['momento_cobro'] ?? null, ['inicio', 'firma', 'desembolso_descuento'], true);
                if ($inicial && empty($comision['modalidad_cobro']) && ($comision['momento_cobro'] ?? null) === 'inicio') {
                    $validator->errors()->add("version.comisiones.$index.modalidad_cobro", 'Seleccione cómo se cobrará la comisión inicial.');
                }
                if (! $inicial && ! empty($comision['modalidad_cobro'])) {
                    $validator->errors()->add("version.comisiones.$index.modalidad_cobro", 'La modalidad inicial solo aplica a comisiones cobradas al inicio.');
                }
                if (($comision['incluye_cat'] ?? false) && ! ($comision['obligatoria'] ?? false)) {
                    $validator->errors()->add("version.comisiones.$index.incluye_cat", 'Una comisión opcional no puede incluirse automáticamente en el CAT del escenario base.');
                }
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'clave' => 'clave', 'nombre' => 'nombre', 'version.monto_minimo' => 'monto mínimo',
            'version.monto_maximo' => 'monto máximo', 'version.tasa_ordinaria_anual' => 'tasa ordinaria anual',
            'version.tasa_moratoria_anual' => 'tasa moratoria anual', 'version.periodicidades' => 'periodicidades',
            'version.reglas.metodos_amortizacion' => 'métodos de amortización',
            'version.comisiones.*.modalidad_cobro' => 'modalidad de cobro inicial',
        ];
    }

    public function messages(): array
    {
        return [
            'version.monto_maximo.gte' => 'El monto máximo debe ser mayor o igual que el monto mínimo.',
            'version.periodicidades.*.plazo_maximo.gte' => 'El plazo máximo debe ser mayor o igual que el plazo mínimo.',
        ];
    }
}
