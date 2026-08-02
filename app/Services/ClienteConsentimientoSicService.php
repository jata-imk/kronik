<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Models\Cliente;
use App\Models\ClienteConsentimientoSic;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClienteConsentimientoSicService
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

    public function store(Cliente $cliente, array $data, UploadedFile $file, User $user): ClienteConsentimientoSic
    {
        $disk = 'local';
        $path = $file->store("clientes/{$cliente->id}/consentimientos-sic", $disk);

        try {
            return DB::transaction(function () use ($cliente, $data, $file, $user, $disk, $path) {
                $consentimiento = $cliente->consentimientosSic()->create([
                    ...$data,
                    'registrado_por' => $user->id,
                    'evidencia_disk' => $disk,
                    'evidencia_path' => $path,
                    'evidencia_nombre_original' => $file->getClientOriginalName(),
                    'evidencia_mime_type' => $file->getMimeType(),
                    'evidencia_tamano_bytes' => $file->getSize(),
                ]);

                $this->activityLog->log(
                    event: ActivityEvent::ClientSicConsentCreated,
                    description: 'Consentimiento SIC registrado',
                    subject: $cliente,
                    metadata: [
                        'related' => ['type' => 'cliente_consentimiento_sic', 'id' => $consentimiento->id],
                        'state' => 'vigente',
                    ],
                    causer: $user,
                );

                return $consentimiento;
            });
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw $exception;
        }
    }

    public function revoke(ClienteConsentimientoSic $consentimiento, User $user): ClienteConsentimientoSic
    {
        $consentimiento->update(['revocado_en' => now()]);

        $this->activityLog->log(
            event: ActivityEvent::ClientSicConsentRevoked,
            description: 'Consentimiento SIC revocado',
            subject: $consentimiento->cliente,
            metadata: [
                'related' => ['type' => 'cliente_consentimiento_sic', 'id' => $consentimiento->id],
                'state' => 'revocado',
            ],
            causer: $user,
        );

        return $consentimiento->refresh();
    }

    public function deleteFilesFor(Cliente $cliente): void
    {
        $cliente->consentimientosSic()
            ->get(['evidencia_disk', 'evidencia_path'])
            ->each(fn (ClienteConsentimientoSic $consentimiento) => Storage::disk($consentimiento->evidencia_disk)->delete($consentimiento->evidencia_path));
    }
}
