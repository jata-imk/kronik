<?php

namespace App\Services;

use App\Models\CalendarioCrediticioVersion;
use App\Models\Cliente;
use App\Models\Credito;
use App\Models\CreditoPago;
use App\Models\DocumentoGenerado;
use App\Models\ProductoVersion;
use App\Models\SicQuery;
use App\Models\Solicitud;
use App\Models\Team;
use App\Models\User;
use App\Services\Credito\CalendarioCrediticioService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/** Navigation checks reuse domain policies; they never execute a destination controller. */
class MenubarDestinoService
{
    public function sinContexto(string $name, User $actor): bool
    {
        $permissions = match (true) {
            $name === 'clientes.create' => ['create clientes'],
            $name === 'clientes.edit' => ['update clientes'],
            str_starts_with($name, 'clientes.historial-crediticio.'), $name === 'consultas-sic.show' => ['read clientes', 'read historial-crediticio'],
            str_starts_with($name, 'clientes.') => ['read clientes'],
            str_starts_with($name, 'circulo-credito.') => ['read clientes', 'create circulo-credito'],
            $name === 'solicitudes.create' => ['read solicitudes', 'read clientes', 'create solicitudes'],
            $name === 'solicitudes.edit' => ['read solicitudes', 'read clientes', 'update solicitudes'],
            $name === 'solicitudes.desembolso.create' => ['read solicitudes', 'read clientes', 'read documentos', 'read creditos', 'disburse creditos'],
            $name === 'solicitudes.paquete.show' => ['read solicitudes', 'read clientes', 'read documentos'],
            str_starts_with($name, 'solicitudes.') => ['read solicitudes', 'read clientes'],
            $name === 'creditos.pagos.create' => ['read creditos', 'read solicitudes', 'read clientes', 'pay creditos'],
            $name === 'creditos.anticipos.create' => ['read creditos', 'read solicitudes', 'read clientes'],
            str_starts_with($name, 'creditos.') => ['read creditos', 'read solicitudes', 'read clientes'],
            $name === 'documentos-generados.view' => ['read documentos'],
            $name === 'originacion-politicas.show' => ['read productos-crediticios'],
            $name === 'productos-crediticios.calendario.show' => [CalendarioCrediticioService::CONSULTAR],
            $name === 'teams.show' => [],
            default => null,
        };
        if ($permissions === null) {
            return $this->permitido($name, [], $actor);
        }

        return collect($permissions)->every(fn ($permission) => Gate::forUser($actor)->allows($permission))
            && ($name !== 'creditos.anticipos.create' || $actor->can('prepay creditos') || $actor->can('settle creditos'));
    }

    public function permitido(string $name, array $params, User $actor): bool
    {
        $gate = Gate::forUser($actor);
        $all = fn (array $permissions) => collect($permissions)->every(fn ($permission) => $gate->allows($permission));
        $model = function (string $key, string $class) use ($params): ?Model {
            $value = $params[$key] ?? null;

            return $value instanceof $class ? $value : (is_scalar($value) && $value !== '' ? $class::find($value) : null);
        };

        if (str_starts_with($name, 'clientes.historial-crediticio.')) {
            return $all(['read clientes', 'read historial-crediticio'])
                && ($name !== 'clientes.historial-crediticio.show' || (($cliente = $model('cliente', Cliente::class)) && $gate->allows('view', $cliente)));
        }
        if (str_starts_with($name, 'clientes.')) {
            if ($name === 'clientes.create') {
                return $gate->allows('create', Cliente::class);
            }
            if (in_array($name, ['clientes.show', 'clientes.edit', 'clientes.expediente.show'], true)) {
                $cliente = $model('cliente', Cliente::class);

                return $cliente && $gate->allows($name === 'clientes.edit' ? 'update' : 'view', $cliente);
            }

            return in_array($name, ['clientes.index', 'clientes.expedientes.index'], true) && $gate->allows('viewAny', Cliente::class);
        }
        if (str_starts_with($name, 'solicitudes.')) {
            if ($name === 'solicitudes.create') {
                $cliente = $model('cliente_id', Cliente::class);

                return $gate->allows('create', Solicitud::class) && (! isset($params['cliente_id'])
                    || ($cliente && $actor->operaEnSucursal($cliente->sucursal_id) && $gate->allows('view', $cliente)));
            }
            if (in_array($name, ['solicitudes.show', 'solicitudes.edit', 'solicitudes.paquete.show', 'solicitudes.desembolso.create'], true)) {
                $solicitud = $model('solicitud', Solicitud::class);
                $ability = match ($name) {
                    'solicitudes.edit' => 'update', 'solicitudes.paquete.show' => 'viewPackage',
                    'solicitudes.desembolso.create' => 'disburse', default => 'view',
                };

                return $solicitud && $gate->allows($ability, $solicitud);
            }

            return in_array($name, ['solicitudes.index', 'solicitudes.trabajo'], true) && $gate->allows('viewAny', Solicitud::class);
        }
        if (str_starts_with($name, 'creditos.')) {
            if ($name === 'creditos.index') {
                return $gate->allows('viewAny', Credito::class);
            }
            $credito = $model('credito', Credito::class);
            if (! $credito || ! $gate->allows('view', $credito)) {
                return false;
            }

            return match ($name) {
                'creditos.pagos.create' => $gate->allows('pay', $credito) && $actor->operaEnSucursal($credito->sucursal_id),
                'creditos.anticipos.create' => ($gate->allows('prepay', $credito) || $gate->allows('settle', $credito)) && $actor->operaEnSucursal($credito->sucursal_id),
                'creditos.pagos.show' => ($pago = $model('pago', CreditoPago::class)) && (int) $pago->credito_id === (int) $credito->id,
                default => $name === 'creditos.show',
            };
        }
        if ($name === 'documentos-generados.view') {
            return ($documento = $model('documento', DocumentoGenerado::class)) && $gate->allows('view', $documento);
        }
        if ($name === 'consultas-sic.show') {
            return $all(['read clientes', 'read historial-crediticio']) && ($consulta = $model('consulta', SicQuery::class)) && $gate->allows('view', $consulta->cliente);
        }
        if (str_starts_with($name, 'circulo-credito.')) {
            $cliente = $model('cliente', Cliente::class);

            return in_array($name, ['circulo-credito.index', 'circulo-credito.create'], true) && $all(['read clientes', 'create circulo-credito']) && (! isset($params['cliente'])
                || ($cliente && $actor->operaEnSucursal($cliente->sucursal_id) && $gate->allows('update', $cliente)));
        }
        if ($name === 'teams.show') {
            return ($team = $model('team', Team::class)) && $gate->allows('view', $team);
        }
        if ($name === 'admin.roles.index') {
            $teamId = $params['team'] ?? $actor->current_team_id;
            $team = is_scalar($teamId) ? Team::find($teamId) : null;

            return $team && app(TeamContext::class)->allows($actor, $team, 'read roles');
        }
        if ($name === 'originacion-politicas.show') {
            return $gate->allows('read productos-crediticios') && $model('version', ProductoVersion::class) !== null;
        }
        if ($name === 'productos-crediticios.calendario.show') {
            return $gate->allows(CalendarioCrediticioService::CONSULTAR)
                && ($version = $model('version', CalendarioCrediticioVersion::class))
                && ($version->publicada_en !== null || $gate->allows(CalendarioCrediticioService::EDITAR));
        }
        $permissions = match ($name) {
            'evaluacion-solicitudes.index' => ['read solicitudes', 'read clientes', 'read evaluacion-solicitudes'],
            'cumplimiento.index' => ['read solicitudes', 'read clientes', 'read cumplimiento'],
            'productos-crediticios.index' => ['read productos-crediticios'],
            'productos-crediticios.calendario.index' => [CalendarioCrediticioService::CONSULTAR],
            'plantillas-documentos.index' => ['read plantillas-documentos'],
            'admin.dashboard' => ['access admin'],
            'admin.users.index' => ['read users'], 'admin.users.activity' => ['read activity-log'],
            'admin.teams.index' => ['read teams'], 'admin.roles.index' => ['read roles'],
            'admin.sucursales.index' => ['read sucursales'], 'admin.menubar-items.index' => ['read menubar-items'],
            'admin.configuracion-empresa.index' => ['read configuracion-empresa'],
            default => null,
        };
        if ($permissions !== null) {
            return $all($permissions);
        }
        if (in_array($name, ['dashboard', 'profile.show'], true)) {
            return true;
        }
        if ($name === 'api-tokens.index') {
            return \Laravel\Jetstream\Jetstream::hasApiFeatures();
        }

        // Legacy destinations remain stored, but an unclassified destination must
        // not advertise privileged screens or JSON/helper endpoints.
        return false;
    }

    public function urlSegura(?string $url): bool
    {
        return is_string($url) && $url !== '' && ! preg_match('/[\x00-\x20\\\\]/', $url)
            && (str_starts_with($url, '/') && ! str_starts_with($url, '//') || in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true));
    }
}
