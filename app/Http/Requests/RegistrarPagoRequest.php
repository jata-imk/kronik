<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $accion = $this->routeIs('creditos.anticipos.*') ? ($this->input('operacion') === 'liquidacion' ? 'settle' : 'prepay') : 'pay';

        return $this->user()->can($accion, $this->route('credito'));
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('referencia'))) {
            $this->merge(['referencia' => trim($this->input('referencia'))]);
        }
    }

    public function rules(): array
    {
        $previa = $this->routeIs('creditos.pagos.preview', 'creditos.anticipos.preview');
        $anticipo = $this->routeIs('creditos.anticipos.*');

        return ['operacion' => [$anticipo ? 'required' : 'sometimes', \Illuminate\Validation\Rule::in($anticipo ? ['prepago', 'liquidacion'] : ['pago'])],
            'comisiones_opcionales' => ['sometimes', 'array', 'max:50'], 'comisiones_opcionales.*' => ['integer', 'min:0', 'distinct'],
            'fecha_pago' => ['required', 'date_format:Y-m-d'], 'importe' => [$anticipo && $previa && $this->input('operacion') === 'liquidacion' ? 'nullable' : 'required', 'numeric', 'regex:/^\d{1,14}(\.\d{1,2})?$/D', 'gt:0'],
            'referencia' => ['required', 'string', 'min:5', 'max:120'], 'idempotency_key' => ['required', 'uuid'],
            'confirmacion_qa' => $previa ? ['sometimes', 'boolean'] : ['accepted'],
            'previa_hash' => $previa ? ['nullable'] : ['required', 'string', 'size:64']];
    }

    public function messages(): array
    {
        return ['importe.regex' => 'Indica el importe sin separadores y con máximo dos decimales.',
            'confirmacion_qa.accepted' => 'Confirma que el pago es sintético y exclusivamente de QA.',
            'previa_hash.required' => 'Revisa la distribución del pago antes de confirmar.'];
    }

    public function attributes(): array
    {
        return ['fecha_pago' => 'fecha efectiva del pago', 'referencia' => 'referencia única del pago', 'importe' => 'importe recibido', 'previa_hash' => 'distribución revisada'];
    }
}
