<?php

namespace App\Services\SICs;

use App\Models\Cliente;
use App\Models\ClienteConsentimientoSic;
use App\Models\SicQuery;

final class SicRequisito
{
    public function evaluar(int $revisionId, Cliente $cliente, array $reglas, ?array $aprobado = null): ?array
    {
        if (empty($reglas['sic_apis']) || ! isset($reglas['sic_sin_score'])) {
            return null; // Old policies must be explicitly versioned before accepting QA SIC.
        }
        $queries = SicQuery::where('cliente_id', $cliente->id)->where('solicitud_revision_id', $revisionId)
            ->where('ambiente', 'qa')->where('estado_operacion', 'completada')
            ->whereHas('api', fn ($q) => $q->whereIn('clave', $reglas['sic_apis']))
            ->when($aprobado, fn ($q) => $q->whereKey($aprobado['consulta_id'] ?? 0))->latest('id')->get();
        $service = app(SicOperacionService::class);
        foreach ($queries as $q) {
            try {
                $consent = ClienteConsentimientoSic::findOrFail($q->consentimiento_id);
                $resultado = $service->leer($q, 'resultado');
                if (! $resultado || ($resultado['demostrativo'] ?? false)) {
                    continue;
                }
                if (! hash_equals($q->identidad_hash, $service->identidadHash($cliente))
                    || ! hash_equals($q->consentimiento_hash, $service->consentimientoHash($consent))) {
                    continue;
                }
                $estado = $resultado['estado'] ?? null;
                if ($estado !== 'con_score' && ! ($estado === 'sin_score' && $reglas['sic_sin_score'] === 'evaluacion_manual')) {
                    continue;
                }
                $evidencia = ['consulta_id' => $q->id, 'resultado_hash' => SicOperacionService::hash($resultado),
                    'consentimiento_hash' => $q->consentimiento_hash, 'ambiente' => 'qa'];
                if (! $aprobado || $aprobado === $evidencia) {
                    return $evidencia;
                }
            } catch (\Illuminate\Validation\ValidationException|\Illuminate\Database\Eloquent\ModelNotFoundException) {
                continue;
            }
        }

        return null;
    }
}
