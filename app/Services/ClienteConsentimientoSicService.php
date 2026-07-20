<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\ClienteConsentimientoSic;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClienteConsentimientoSicService
{
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

                activity()
                    ->performedOn($cliente)
                    ->causedBy($user)
                    ->withProperties(['consentimiento_id' => $consentimiento->id, 'medio' => $consentimiento->medio->value])
                    ->log('Consentimiento SIC registrado');

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

        activity()
            ->performedOn($consentimiento->cliente)
            ->causedBy($user)
            ->withProperties(['consentimiento_id' => $consentimiento->id])
            ->log('Consentimiento SIC revocado');

        return $consentimiento->refresh();
    }

    public function deleteFilesFor(Cliente $cliente): void
    {
        $cliente->consentimientosSic()
            ->get(['evidencia_disk', 'evidencia_path'])
            ->each(fn (ClienteConsentimientoSic $consentimiento) => Storage::disk($consentimiento->evidencia_disk)->delete($consentimiento->evidencia_path));
    }
}
