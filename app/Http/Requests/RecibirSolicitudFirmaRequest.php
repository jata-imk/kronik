<?php

namespace App\Http\Requests;

use App\Rules\ArchivoDocumentoSeguro;
use App\Services\FechaEmpresa;
use Illuminate\Foundation\Http\FormRequest;

class RecibirSolicitudFirmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('receiveSignature', $this->route('solicitud'));
    }

    public function rules(): array
    {
        return ['archivo' => ['required', 'file', 'mimetypes:application/pdf', new ArchivoDocumentoSeguro, 'max:'.config('documentos.max_upload_kb')],
            'fecha_firma' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.app(FechaEmpresa::class)->hoy()->toDateString()],
            'idempotency_key' => ['required', 'uuid'], 'lock_version' => ['required', 'integer', 'min:0'],
            'confirmacion_qa' => ['accepted']];
    }

    public function messages(): array
    {
        return ['archivo.mimetypes' => 'Adjunta un PDF con todas las páginas del contrato firmado y su tabla de pagos.',
            'confirmacion_qa.accepted' => 'Confirma que esta firma es una prueba QA sin validez contractual.'];
    }
}
