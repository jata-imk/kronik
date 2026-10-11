<?php

namespace App\Services\SICs;

use App\Enums\ActivityEvent;
use App\Models\Cliente;
use App\Models\ClienteConsentimientoSic;
use App\Models\SicApi;
use App\Models\SicEvento;
use App\Models\SicEvidencia;
use App\Models\SicQuery;
use App\Models\SolicitudRevision;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\FechaEmpresa;
use App\Services\SolicitudExpedienteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class SicOperacionService
{
    public function preparar(Cliente $cliente, array $data, User $actor): SicQuery
    {
        CirculoSandboxGateway::habilitado();

        if (! array_key_exists($data['api'] ?? '', config('sic.servicios', []))) {
            $this->error('Selecciona uno de los tres servicios SIC disponibles en QA.');
        }

        return DB::transaction(function () use ($cliente, $data, $actor) {
            $cliente = Cliente::lockForUpdate()->findOrFail($cliente->id);
            $this->autorizar($cliente, $actor);
            $consentimiento = ClienteConsentimientoSic::where('cliente_id', $cliente->id)->findOrFail($data['consentimiento_id']);
            $consentimientoHash = $this->consentimientoHash($consentimiento);
            $revision = isset($data['solicitud_revision_id']) ? SolicitudRevision::findOrFail($data['solicitud_revision_id']) : null;
            $identidadHash = $this->identidadHash($cliente);
            $this->revisionValida($revision, $cliente);
            $hash = self::hash([$cliente->id, $data['api'], $actor->id, $actor->current_team_id, $actor->current_sucursal_id,
                $consentimiento->id, $consentimientoHash, $revision?->id, $identidadHash]);
            if ($existing = SicQuery::where('idempotency_key', $data['idempotency_key'])->first()) {
                if (! hash_equals($existing->payload_hash, $hash)) {
                    $this->error('Este envío ya fue utilizado con otros datos. Revisa la consulta original.');
                }

                return $existing;
            }
            $api = SicApi::where('clave', $data['api'])->whereHas('sic', fn ($q) => $q->where('clave', 'circulo-credito'))->firstOrFail();
            if ($abierta = SicQuery::where('cliente_id', $cliente->id)->where('sic_api_id', $api->id)->where('ambiente', 'qa')
                ->whereIn('estado_operacion', ['preparada', 'enviando', 'incierta'])->first()) {
                $this->error('Ya existe un intento abierto para este servicio: consulta #'.$abierta->id.'. Revisa su resultado o conciliación antes de enviar otra consulta.');
            }
            $query = SicQuery::create(['cliente_id' => $cliente->id, 'sic_id' => $api->sic_id, 'sic_api_id' => $api->id,
                'fecha_consulta' => now(), 'status' => 'pending', 'ambiente' => 'qa', 'estado_operacion' => 'preparada',
                'actor_id' => $actor->id, 'team_id' => $actor->current_team_id, 'sucursal_id' => $cliente->sucursal_id,
                'consentimiento_id' => $consentimiento->id, 'solicitud_revision_id' => $revision?->id,
                'idempotency_key' => $data['idempotency_key'], 'payload_hash' => $hash, 'identidad_hash' => $identidadHash,
                'consentimiento_hash' => $consentimientoHash]);
            $payload = app(SicPeticion::class)->construir($cliente, $data['api'], (string) $query->id);
            $this->evidencia($query, 'peticion', $payload);
            SicEvento::create(['sic_query_id' => $query->id, 'estado' => 'preparada', 'actor_id' => $actor->id]);
            \App\Jobs\ConsultarSicQa::dispatch($query->id)->afterCommit();

            return $query;
        });
    }

    public function ejecutar(int $id): void
    {
        $query = DB::transaction(function () use ($id) {
            $q = SicQuery::lockForUpdate()->findOrFail($id);
            if ($q->estado_operacion !== 'preparada') {
                return null; // Redelivery must never send a second provider POST.
            }
            try {
                CirculoSandboxGateway::habilitado();
                $actor = User::findOrFail($q->actor_id);
                $actor->forceFill(['current_team_id' => $q->team_id, 'current_sucursal_id' => $q->sucursal_id]);
                $actor->unsetRelations();
                $this->autorizar($q->cliente, $actor);
                if (! hash_equals($q->identidad_hash, $this->identidadHash($q->cliente))
                    || ! hash_equals($q->consentimiento_hash, $this->consentimientoHash(ClienteConsentimientoSic::findOrFail($q->consentimiento_id)))) {
                    $this->error('La identidad o autorización cambió antes del envío.');
                }
                $this->revisionValida($q->solicitud_revision_id ? SolicitudRevision::findOrFail($q->solicitud_revision_id) : null, $q->cliente);
                $this->transicion($q, 'enviando', 'pending');

                return $q;
            } catch (\Throwable) {
                $this->transicion($q, 'cancelada', 'error');

                return null;
            }
        });
        if (! $query) {
            return;
        }
        try {
            $result = app(SicGateway::class)->consultar($query->api->clave, $this->leer($query, 'peticion'));
            DB::transaction(function () use ($query, $result) {
                $q = SicQuery::lockForUpdate()->findOrFail($query->id);
                if ($q->estado_operacion !== 'enviando') {
                    // A late response remains evidence even after an uncertain send was reconciled.
                    if (! $q->evidencias()->where('tipo', 'respuesta_tardia')->exists()) {
                        $this->evidencia($q, 'respuesta_tardia', $result['respuesta']);
                        $this->evidencia($q, 'resultado_tardio', $result['resultado']);
                        SicEvento::create(['sic_query_id' => $q->id, 'estado' => 'respuesta_tardia', 'actor_id' => $q->actor_id]);
                    }

                    return;
                }
                $this->evidencia($q, 'respuesta', $result['respuesta']);
                $this->evidencia($q, 'resultado', $result['resultado']);
                $q->http_status = $result['http_status'];
                $ok = in_array($result['resultado']['estado'], ['con_score', 'sin_score', 'bloqueado'], true);
                $this->transicion($q, $ok ? 'completada' : 'error', $ok ? 'success' : 'error');
                $event = match ($q->api->clave) {
                    'fico_score_v2' => ActivityEvent::ClientSicFicoScoreV2Queried,
                    'fintech' => ActivityEvent::ClientSicFintechScoreQueried,
                    default => ActivityEvent::ClientSicCreditReportFicoQueried,
                };
                app(ActivityLogService::class)->log($event, 'Consulta SIC QA finalizada', $q->cliente,
                    ['related' => ['type' => 'sic_query', 'id' => $q->id]], User::find($q->actor_id), teamId: $q->team_id, sucursalId: $q->sucursal_id);
            });
        } catch (\Throwable) {
            // Exceptions can contain credentials/payloads: never serialize or log them.
            DB::transaction(function () use ($query) {
                $q = SicQuery::lockForUpdate()->findOrFail($query->id);
                if ($q->estado_operacion === 'enviando') {
                    $this->transicion($q, 'incierta', 'pending');
                }
            });
        }
    }

    public function leer(SicQuery $query, string $tipo): ?array
    {
        $e = $query->evidencias()->where('tipo', $tipo)->first();
        if (! $e) {
            return null;
        }
        if (! hash_equals($e->contenido_hash, self::hash($e->contenido))) {
            $this->error('La evidencia SIC cambió. Solicita revisión al administrador.');
        }

        return $e->contenido;
    }

    public function evidencia(SicQuery $query, string $tipo, array $contenido): void
    {
        SicEvidencia::create(['sic_query_id' => $query->id, 'tipo' => $tipo, 'contenido' => $contenido, 'contenido_hash' => self::hash($contenido)]);
    }

    public function consentimientoHash(ClienteConsentimientoSic $c): string
    {
        $hoy = app(FechaEmpresa::class)->hoy();
        if ($c->revocado_en || ! $c->otorgado_en || $c->otorgado_en->gt(now()) || ($c->vence_en && $c->vence_en->lt($hoy->startOfDay()))
            || ! $c->evidencia_disk || ! $c->evidencia_path || ! Storage::disk($c->evidencia_disk)->exists($c->evidencia_path)) {
            $this->error('Se necesita un consentimiento SIC vigente con evidencia disponible.');
        }

        return self::hash([$c->id, $c->otorgado_en->toISOString(), $c->vence_en?->toDateString(),
            hash('sha256', Storage::disk($c->evidencia_disk)->get($c->evidencia_path))]);
    }

    public function identidadHash(Cliente $cliente): string
    {
        return self::hash(app(SolicitudExpedienteService::class)->identidad($cliente));
    }

    private function revisionValida(?SolicitudRevision $revision, Cliente $cliente): void
    {
        if (! $revision) {
            return;
        }
        $solicitud = \App\Models\Solicitud::findOrFail($revision->solicitud_id);
        if ($solicitud->cliente_id !== $cliente->id || $solicitud->revisiones()->latest('numero')->value('id') !== $revision->id
            || ! hash_equals(self::hash($revision->snapshot['identidad_cliente'] ?? []), $this->identidadHash($cliente))) {
            $this->error('Selecciona la revisión vigente de una solicitud con la misma identidad.');
        }
    }

    private function autorizar(Cliente $cliente, User $actor): void
    {
        $registrar = app(\Spatie\Permission\PermissionRegistrar::class);
        $original = $registrar->getPermissionsTeamId();
        try {
            $registrar->setPermissionsTeamId($actor->current_team_id);
            $actor->unsetRelation('roles')->unsetRelation('permissions');
            Gate::forUser($actor)->authorize('create circulo-credito');
            Gate::forUser($actor)->authorize('viewAny', Cliente::class);
            Gate::forUser($actor)->authorize('update', $cliente);
            if (! $actor->isActive() || ! $actor->currentTeam?->activo || ! $actor->currentSucursal?->activa || ! $actor->belongsToSucursal($actor->currentSucursal)
                || (int) $actor->current_sucursal_id !== (int) $cliente->sucursal_id
                || (! $actor->is_super_admin && ! $actor->allTeams()->contains('id', $actor->current_team_id))) {
                $this->error('Selecciona un equipo autorizado y la sucursal activa responsable del cliente.');
            }
        } finally {
            $registrar->setPermissionsTeamId($original);
        }
    }

    public function conciliar(SicQuery $query, array $data, User $actor): void
    {
        Gate::forUser($actor)->authorize('reconcile circulo-credito');
        Gate::forUser($actor)->authorize('read historial-crediticio');
        $this->autorizar($query->cliente, $actor);
        DB::transaction(function () use ($query, $data, $actor) {
            $q = SicQuery::lockForUpdate()->findOrFail($query->id);
            if ($q->ambiente !== 'qa' || $q->estado_operacion !== 'incierta') {
                $this->error('Solo se puede conciliar un intento SIC QA incierto. Actualiza la consulta.');
            }
            $this->evidencia($q, 'conciliacion', ['actor_id' => $actor->id, 'motivo' => $data['motivo'], 'referencia' => $data['referencia'], 'fecha' => now()->toISOString(), 'resultado' => 'sin_resultado_acreditable']);
            $q->forceFill(['estado_operacion' => 'conciliada_sin_resultado', 'status' => 'error'])->save();
            SicEvento::create(['sic_query_id' => $q->id, 'estado' => 'conciliada_sin_resultado', 'actor_id' => $actor->id]);
            app(ActivityLogService::class)->log(ActivityEvent::SicAttemptReconciled, 'Intento SIC QA conciliado sin resultado acreditable', $q->cliente,
                ['related' => ['type' => 'sic_query', 'id' => $q->id]], $actor);
        });
    }

    public function marcarInciertas(): int
    {
        $count = 0;
        SicQuery::where('ambiente', 'qa')->where('estado_operacion', 'enviando')->where('updated_at', '<', now()->subMinutes(2))
            ->orderBy('id')->chunkById(100, function ($queries) use (&$count) {
                foreach ($queries as $query) {
                    DB::transaction(function () use ($query, &$count) {
                        $q = SicQuery::lockForUpdate()->findOrFail($query->id);
                        if ($q->estado_operacion === 'enviando' && $q->updated_at->lt(now()->subMinutes(2))) {
                            $this->transicion($q, 'incierta', 'pending');
                            $count++;
                        }
                    });
                }
            });

        return $count;
    }

    private function transicion(SicQuery $q, string $estado, string $status): void
    {
        $q->forceFill(['estado_operacion' => $estado, 'status' => $status])->save();
        SicEvento::create(['sic_query_id' => $q->id, 'estado' => $estado, 'actor_id' => $q->actor_id]);
    }

    public static function hash(array $data): string
    {
        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
    }

    private function error(string $message): never
    {
        throw ValidationException::withMessages(['sic' => $message]);
    }
}
