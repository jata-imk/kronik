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

    public function request(Cliente $cliente, DocumentoPlantillaVersion $version, ?ClienteGarantia $garantia, string $idempotencyKey, User $user, bool $confirmDuplicate = false): DocumentoGenerado
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
        if ($version->plantilla->tipo !== DocumentoPlantillaTipo::Garantia && $garantia) {
            throw ValidationException::withMessages(['garantia_id' => 'La plantilla seleccionada no utiliza una garantía.']);
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

        $document = DB::transaction(function () use ($cliente, $version, $garantia, $idempotencyKey, $resolved, $user, $confirmDuplicate) {
            Cliente::query()->whereKey($cliente->id)->lockForUpdate()->firstOrFail();
            $matching = $this->findExisting($cliente, $version, $garantia);

            if ($matching && in_array($matching->estado, [DocumentoGeneradoEstado::Pendiente, DocumentoGeneradoEstado::Procesando], true)) {
                throw ValidationException::withMessages([
                    'version_id' => 'Ya existe una generación en curso para esta plantilla y contexto. Espere a que termine antes de solicitar otra.',
                ]);
            }

            if ($matching?->estado === DocumentoGeneradoEstado::Generado && ! $confirmDuplicate) {
                throw ValidationException::withMessages([
                    'confirm_duplicate' => 'Ya existe un PDF generado para esta plantilla y contexto. Confirme que desea crear otra copia trazable.',
                ]);
            }

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

    public function findExisting(Cliente $cliente, DocumentoPlantillaVersion $version, ?ClienteGarantia $garantia): ?DocumentoGenerado
    {
        $documentable = $garantia ?? $cliente;

        return DocumentoGenerado::query()
            ->where('cliente_id', $cliente->id)
            ->where('documento_plantilla_version_id', $version->id)
            ->where('documentable_type', $documentable->getMorphClass())
            ->where('documentable_id', $documentable->getKey())
            ->whereIn('estado', [
                DocumentoGeneradoEstado::Pendiente->value,
                DocumentoGeneradoEstado::Procesando->value,
                DocumentoGeneradoEstado::Generado->value,
            ])
            ->orderByRaw("CASE WHEN estado IN ('pendiente', 'procesando') THEN 0 ELSE 1 END")
            ->latest('solicitado_en')
            ->first();
    }
}
