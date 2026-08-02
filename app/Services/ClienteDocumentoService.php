<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\ClienteDocumentoEstado;
use App\Enums\ClienteDocumentoTipo;
use App\Models\Cliente;
use App\Models\ClienteDocumento;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ClienteDocumentoService
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

    public function upload(Cliente $cliente, array $data, UploadedFile $file): ClienteDocumento
    {
        $disk = 'local';
        $path = $file->store("clientes/{$cliente->id}/documentos", $disk);

        try {
            return DB::transaction(function () use ($cliente, $data, $file, $disk, $path) {
                $tipo = ClienteDocumentoTipo::from($data['tipo']);
                $reemplaza = $this->findReplacement($cliente, $tipo, $data['reemplaza_documento_id'] ?? null);

                if ($reemplaza && $reemplaza->estado === ClienteDocumentoEstado::Pendiente && ! $reemplaza->path) {
                    $reemplaza->update($this->fileAttributes($data, $file, $disk, $path));
                    $documento = $reemplaza->refresh();
                } else {
                    if ($reemplaza) {
                        $reemplaza->update(['es_actual' => false]);
                    }

                    $documento = $cliente->documentos()->create([
                        ...$this->fileAttributes($data, $file, $disk, $path),
                        'tipo' => $tipo->value,
                        'version' => ($reemplaza?->version ?? 0) + 1,
                        'reemplaza_documento_id' => $reemplaza?->id,
                        'es_actual' => true,
                    ]);
                }

                $this->activityLog->log(
                    event: ActivityEvent::ClientDocumentReceived,
                    description: 'Documento de cliente recibido',
                    subject: $cliente,
                    metadata: [
                        'related' => ['type' => 'cliente_documento', 'id' => $documento->id],
                        'state' => ClienteDocumentoEstado::Recibido->value,
                    ],
                    causer: auth()->user(),
                );

                return $documento;
            });
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw $exception;
        }
    }

    public function changeStatus(ClienteDocumento $documento, ClienteDocumentoEstado $estado, User $reviewer, ?string $reason = null): ClienteDocumento
    {
        if (! $documento->es_actual) {
            throw ValidationException::withMessages([
                'estado' => 'Las versiones historicas no se pueden modificar.',
            ]);
        }

        if (! $this->canTransition($documento->estado, $estado)) {
            throw ValidationException::withMessages([
                'estado' => 'La transicion de estado documental no es valida.',
            ]);
        }

        if ($estado === ClienteDocumentoEstado::Rechazado && blank($reason)) {
            throw ValidationException::withMessages([
                'motivo_rechazo' => 'El motivo de rechazo es obligatorio.',
            ]);
        }

        $documento->update([
            'estado' => $estado,
            'revisado_en' => now(),
            'revisado_por' => $reviewer->id,
            'motivo_rechazo' => $estado === ClienteDocumentoEstado::Rechazado ? $reason : null,
        ]);

        $this->activityLog->log(
            event: ActivityEvent::ClientDocumentStatusUpdated,
            description: 'Estado documental actualizado',
            subject: $documento->cliente,
            metadata: [
                'related' => ['type' => 'cliente_documento', 'id' => $documento->id],
                'state' => $estado->value,
            ],
            causer: $reviewer,
        );

        return $documento->refresh();
    }

    public function canTransition(ClienteDocumentoEstado $from, ClienteDocumentoEstado $to): bool
    {
        $allowed = match ($from) {
            ClienteDocumentoEstado::Recibido => [ClienteDocumentoEstado::Validado, ClienteDocumentoEstado::Rechazado, ClienteDocumentoEstado::Vencido],
            ClienteDocumentoEstado::Validado => [ClienteDocumentoEstado::Vencido],
            default => [],
        };

        return in_array($to, $allowed, true);
    }

    public function deleteFilesFor(Cliente $cliente): void
    {
        $cliente->documentos()
            ->whereNotNull('path')
            ->get(['disk', 'path'])
            ->each(fn (ClienteDocumento $documento) => Storage::disk($documento->disk)->delete($documento->path));
    }

    private function findReplacement(Cliente $cliente, ClienteDocumentoTipo $tipo, ?int $replacementId): ?ClienteDocumento
    {
        if ($replacementId) {
            return $cliente->documentos()
                ->whereKey($replacementId)
                ->where('tipo', $tipo->value)
                ->where('es_actual', true)
                ->firstOrFail();
        }

        if ($tipo === ClienteDocumentoTipo::Adicional) {
            return null;
        }

        return $cliente->documentos()
            ->where('tipo', $tipo->value)
            ->where('es_actual', true)
            ->latest('version')
            ->first();
    }

    private function fileAttributes(array $data, UploadedFile $file, string $disk, string $path): array
    {
        return [
            'tipo' => $data['tipo'],
            'nombre' => $data['nombre'] ?? null,
            'estado' => ClienteDocumentoEstado::Recibido,
            'disk' => $disk,
            'path' => $path,
            'nombre_original' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'tamano_bytes' => $file->getSize(),
            'recibido_en' => now(),
            'vence_en' => $data['vence_en'] ?? null,
            'revisado_en' => null,
            'revisado_por' => null,
            'motivo_rechazo' => null,
            'notas' => $data['notas'] ?? null,
        ];
    }
}
