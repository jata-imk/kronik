<?php

namespace App\Services\Documentos;

use App\Enums\ActivityEvent;
use App\Enums\DocumentoGeneradoEstado;
use App\Enums\DocumentoPlantillaTipo;
use App\Enums\DocumentoPlantillaVersionEstado;
use App\Jobs\GenerarDocumentoPdf;
use App\Models\Cliente;
use App\Models\ClienteGarantia;
use App\Models\DocumentoGenerado;
use App\Models\DocumentoPlantillaVersion;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DocumentoGeneracionService
{
    public function __construct(
        private readonly CompiladorPlantillaDocumento $compiler,
        private readonly ResolverVariablesDocumento $resolver,
        private readonly ActivityLogService $activity,
    ) {}

    public function request(Cliente $cliente, DocumentoPlantillaVersion $version, ?ClienteGarantia $garantia, string $idempotencyKey, User $user): DocumentoGenerado
    {
        $version->loadMissing('plantilla');
        if ($version->estado !== DocumentoPlantillaVersionEstado::Activa) {
            throw ValidationException::withMessages(['version_id' => 'Solo puede generarse un documento desde una versión activa.']);
        }
        if ($version->plantilla->tipo === DocumentoPlantillaTipo::Contrato) {
            throw ValidationException::withMessages(['version_id' => 'La generación de contratos se habilitará con el flujo de originación de crédito.']);
        }
        if ($version->plantilla->tipo === DocumentoPlantillaTipo::Garantia && ! $garantia) {
            throw ValidationException::withMessages(['garantia_id' => 'Seleccione la garantía que se documentará.']);
        }
        if ($garantia && $garantia->cliente_id !== $cliente->id) {
            abort(404);
        }

        $existing = DocumentoGenerado::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            if ($existing->cliente_id !== $cliente->id || $existing->documento_plantilla_version_id !== $version->id) {
                throw ValidationException::withMessages(['idempotency_key' => 'El identificador de solicitud ya fue utilizado.']);
            }

            return $existing;
        }

        $keys = $this->compiler->variables($version->encabezado_html ?? '', $version->contenido_html, $version->pie_html ?? '');
        $resolved = $this->resolver->resolve($keys, $cliente, $garantia);

        $document = DB::transaction(function () use ($cliente, $version, $garantia, $idempotencyKey, $resolved, $user) {
            $document = new DocumentoGenerado([
                'documento_plantilla_version_id' => $version->id,
                'cliente_id' => $cliente->id,
                'estado' => DocumentoGeneradoEstado::Pendiente,
                'idempotency_key' => $idempotencyKey,
                'datos_utilizados' => $resolved['values'],
                'metadatos_variables' => $resolved['metadata'],
                'solicitado_en' => now(),
                'creado_por' => $user->id,
            ]);
            $document->documentable()->associate($garantia ?? $cliente);
            $document->save();

            return $document;
        });

        $this->activity->log(
            ActivityEvent::DocumentGenerationRequested,
            'Generación de documento solicitada',
            $cliente,
            ['related' => ['type' => 'documento_generado', 'id' => $document->id], 'state' => DocumentoGeneradoEstado::Pendiente->value],
            $user,
        );
        GenerarDocumentoPdf::dispatch($document->id)->afterCommit();

        return $document;
    }
}
