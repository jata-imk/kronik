<?php

namespace App\Services\Credito;

use App\Models\Credito;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Validation\ValidationException;

/** Selecciona evidencia original. Las operaciones nunca dependen de tablas derivadas. */
final class LecturaCreditoService
{
    public function __construct(private MotorPagos $motor) {}

    public function estado(Credito $credito, ?string $corte = null): EstadoCredito
    {
        try {
            $condiciones = $credito->condiciones;
            $this->verificar($condiciones, $credito->condiciones_hash);
            if (! is_array($condiciones['producto'] ?? null)) {
                $this->error();
            }
            $original = $credito->cronogramas()->where('version', 1)->first();
            if (! $original) {
                $this->error();
            }
            $this->verificar($original->snapshot, $original->snapshot_hash);
            if (! is_array($original->snapshot['tabla'] ?? null)) {
                $this->error();
            }
            $pago = $credito->pagos()->when($corte, fn ($q) => $q->where('fecha_efectiva', '<=', $corte))->latest('id')->first();
            if ($pago) {
                $this->verificar($pago->snapshot, $pago->snapshot_hash);
                if (! is_array($pago->snapshot['despues'] ?? null)) {
                    $this->error();
                }
                $estado = $pago->snapshot['despues'];
                $estado['cronograma_version'] ??= 1;
            } else {
                $estado = empty($condiciones['producto']['politica_mora']) ? null
                    : [...$this->motor->iniciar($condiciones['producto'], $original->snapshot), 'cronograma_version' => 1];
            }
            $version = $estado['cronograma_version'] ?? 1;
            $vigente = $version === 1 ? $original
                : $credito->cronogramas()->where('version', $estado['cronograma_version'])->first();
            if (! $vigente) {
                $this->error();
            }
            $this->verificar($vigente->snapshot, $vigente->snapshot_hash);

            return new EstadoCredito($estado, $vigente->snapshot, $pago?->id, hash('sha256', implode('|', [
                $credito->condiciones_hash, $original->snapshot_hash, $pago?->id, $pago?->snapshot_hash,
                $version, $vigente->snapshot_hash,
            ])), ['condiciones_hash' => $credito->condiciones_hash, 'original_hash' => $original->snapshot_hash,
                'cronograma_hash' => $vigente->snapshot_hash, 'pago_hash' => $pago?->snapshot_hash]);
        } catch (DecryptException $e) {
            $this->error();
        }
    }

    public function verificar(mixed $datos, string $hash): void
    {
        if (! is_array($datos) || ! hash_equals($hash, hash('sha256', json_encode($datos, JSON_THROW_ON_ERROR)))) {
            $this->error();
        }
    }

    private function error(): never
    {
        throw ValidationException::withMessages(['pago' => 'La evidencia del crédito está incompleta o cambió. Solicita revisión al administrador.']);
    }
}
