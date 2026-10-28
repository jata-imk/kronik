<?php

namespace App\Services;

use App\Enums\OperacionEmpresa;
use App\Exceptions\OperacionEmpresaBloqueada;
use App\Models\EmpresaConfiguracion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class EmpresaOperacionService
{
    public function configuracion(): ?EmpresaConfiguracion
    {
        $query = EmpresaConfiguracion::where('singleton_key', 'default');
        // A shared company lock precedes resource locks; configuration writes
        // acquire the exclusive lock and cannot race an admitted operation.
        if (DB::transactionLevel() > 0) {
            $query->sharedLock();
        }

        return $query->first();
    }

    public function permite(OperacionEmpresa $operacion, ?string $estatus = null): bool
    {
        $estatus ??= $this->configuracion()?->estatus ?? 'borrador';

        return match ($operacion) {
            OperacionEmpresa::AtenderCartera => true,
            OperacionEmpresa::Capturar => in_array($estatus, ['borrador', 'activa'], true),
            OperacionEmpresa::ReportarCartera => in_array($estatus, ['activa', 'suspendida'], true),
            default => $estatus === 'activa',
        };
    }

    public function motivo(string $estatus): string
    {
        return $estatus === 'suspendida'
            ? 'La empresa está suspendida. La originación está detenida; puedes atender la cartera existente. Solicita su reactivación a una persona con permiso para configurar la empresa.'
            : 'La empresa está en borrador. Puedes configurar, capturar clientes y solicitudes y simular. Completa los datos y activa la empresa para continuar la originación.';
    }

    public function exigir(OperacionEmpresa $operacion, ?User $actor = null, ?Model $recurso = null,
        ?int $equipoId = null, ?int $sucursalId = null, ?int $iniciadorId = null, bool $sistema = false): void
    {
        $empresa = $this->configuracion();
        $estatus = $empresa?->estatus ?? 'borrador';
        if (! $this->permite($operacion, $estatus)) {
            throw new OperacionEmpresaBloqueada($operacion, $estatus, $this->motivo($estatus), $sistema ? null : $actor,
                $recurso ?? $empresa, $sistema ? $equipoId : ($equipoId ?? $actor?->current_team_id),
                $sistema ? $sucursalId : ($sucursalId ?? $recurso?->getAttribute('sucursal_id') ?? $actor?->current_sucursal_id), $iniciadorId);
        }
    }

    public function exigirDatosDocumentales(): void
    {
        $empresa = $this->configuracion();
        $faltantes = [];
        foreach (['razon_social' => 'razón social', 'rfc' => 'RFC', 'email' => 'correo operativo'] as $campo => $nombre) {
            if (blank($empresa?->$campo)) {
                $faltantes[] = $nombre;
            }
        }
        if ($empresa?->email && ! filter_var($empresa->email, FILTER_VALIDATE_EMAIL)) {
            $faltantes[] = 'correo operativo válido';
        }
        if ($empresa?->rfc && \Illuminate\Support\Facades\Validator::make(['rfc' => $empresa->rfc], ['rfc' => [new \App\Rules\Rfc($empresa->tipo_persona)]])->fails()) {
            $faltantes[] = 'RFC válido';
        }
        if ($faltantes !== []) {
            throw ValidationException::withMessages(['empresa' => 'Completa los datos de la empresa antes de generar documentos: '.implode(', ', $faltantes).'.']);
        }
    }

    public function compartir(): array
    {
        $estatus = $this->configuracion()?->estatus ?? 'borrador';

        return ['estatus' => $estatus, 'motivo' => $estatus === 'activa' ? null : $this->motivo($estatus),
            'capacidades' => collect(OperacionEmpresa::cases())->mapWithKeys(fn ($operacion) => [$operacion->value => $this->permite($operacion, $estatus)])->all()];
    }
}
