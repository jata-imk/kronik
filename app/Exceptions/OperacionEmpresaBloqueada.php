<?php

namespace App\Exceptions;

use App\Enums\ActivityEvent;
use App\Enums\OperacionEmpresa;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class OperacionEmpresaBloqueada extends ValidationException
{
    private bool $registrada = false;

    public function __construct(
        public readonly OperacionEmpresa $operacion,
        public readonly string $estatusEmpresa,
        string $mensaje,
        private readonly ?User $actor,
        private readonly ?Model $recurso,
        private readonly ?int $equipoId,
        private readonly ?int $sucursalId,
        private readonly ?int $iniciadorId = null,
    ) {
        $validator = Validator::make([], []);
        $validator->errors()->add('empresa', $mensaje);
        parent::__construct($validator);
    }

    // HTTP calls record after their transaction has rolled back. Workers record
    // only after persisting their terminal blocked state.
    public function registrar(): void
    {
        if ($this->registrada) {
            return;
        }
        $documento = $this->recurso instanceof \App\Models\DocumentoGenerado ? $this->recurso : null;
        app(ActivityLogService::class)->log(ActivityEvent::CompanyOperationBlocked, 'Operación bloqueada por el estado de la empresa',
            $documento ? $documento->cliente : $this->recurso, array_filter(['state' => $this->estatusEmpresa, 'product' => $this->operacion->value,
                'result' => 'blocked', 'related' => $documento ? ['type' => 'documento_generado', 'id' => $documento->id]
                    : ($this->iniciadorId ? ['type' => 'iniciador', 'id' => $this->iniciadorId] : null)]),
            $this->actor, teamId: $this->equipoId, sucursalId: $this->sucursalId, inheritActorContext: false);
        $this->registrada = true;
    }
}
