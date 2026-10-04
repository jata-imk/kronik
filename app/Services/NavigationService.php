<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

class NavigationService
{
    public function forUser(User $user): array
    {
        $gate = Gate::forUser($user);
        $allowed = fn (array $permissions): bool => $gate->check($permissions);
        $item = fn (string $label, string $icon, string $to, array $activeRoutes = []): array => compact('label', 'icon', 'to', 'activeRoutes');
        $group = fn (string $label, string $icon, array $items): array => compact('label', 'icon', 'items');

        $clientes = [];
        if ($allowed(['read clientes'])) {
            $clientes[] = $item('Listado', 'pi pi-users', 'clientes.index', ['clientes.create', 'clientes.show', 'clientes.edit']);
            $clientes[] = $item('Expedientes KYC', 'pi pi-folder-open', 'clientes.expedientes.index', ['clientes.expediente.*']);
        }
        if ($allowed(['read clientes', 'read historial-crediticio'])) {
            $clientes[] = $item('Consultas SIC', 'pi pi-credit-card', 'clientes.historial-crediticio.index', ['clientes.historial-crediticio.show', 'circulo-credito.*']);
        }

        $solicitudes = [];
        if ($allowed(['read solicitudes', 'read clientes'])) {
            $solicitudes[] = $item('Todas las solicitudes', 'pi pi-list', 'solicitudes.index', ['solicitudes.create', 'solicitudes.show', 'solicitudes.edit', 'solicitudes.paquete.*', 'solicitudes.desembolso.*']);
        }
        if ($allowed(['read solicitudes', 'read clientes', 'read evaluacion-solicitudes'])) {
            $solicitudes[] = $item('Evaluación', 'pi pi-chart-bar', 'evaluacion-solicitudes.index');
        }
        if ($allowed(['read solicitudes', 'read clientes', 'read cumplimiento'])) {
            $solicitudes[] = $item('Cumplimiento', 'pi pi-shield', 'cumplimiento.index');
        }

        $operacion = [];
        if ($clientes) {
            $operacion[] = $group('Clientes', 'pi pi-users', $clientes);
        }
        if ($solicitudes) {
            $operacion[] = $group('Originación', 'pi pi-folder-open', $solicitudes);
        }
        if ($allowed(['read creditos', 'read solicitudes', 'read clientes'])) {
            $operacion[] = $item('Créditos y pagos', 'pi pi-wallet', 'creditos.index', ['creditos.show', 'creditos.pagos.*']);
        }

        $configuracion = [];
        if ($allowed(['read productos-crediticios'])) {
            $configuracion[] = $item('Productos crediticios', 'pi pi-sliders-h', 'productos-crediticios.index', ['originacion-politicas.*']);
        }
        if ($allowed(['read plantillas-documentos'])) {
            $configuracion[] = $item('Documentos y plantillas', 'pi pi-file-edit', 'plantillas-documentos.index');
        }
        if ($allowed(['read configuracion-empresa'])) {
            $configuracion[] = $item('Empresa', 'pi pi-building', 'admin.configuracion-empresa.index');
        }
        if ($allowed(['read sucursales'])) {
            $configuracion[] = $item('Sucursales', 'pi pi-map-marker', 'admin.sucursales.index');
        }

        $administracion = [];
        foreach ([
            ['Resumen', 'pi pi-th-large', 'admin.dashboard', 'access admin'],
            ['Usuarios', 'pi pi-users', 'admin.users.index', 'read users'],
            ['Equipos', 'pi pi-sitemap', 'admin.teams.index', 'read teams'],
            ['Roles y permisos', 'pi pi-key', 'admin.roles.index', 'read roles'],
            ['Actividad', 'pi pi-history', 'admin.users.activity', 'read activity-log'],
            ['Menú contextual', 'pi pi-bars', 'admin.menubar-items.index', 'read menubar-items'],
        ] as [$label, $icon, $to, $permission]) {
            if ($allowed([$permission])) {
                $administracion[] = $item($label, $icon, $to);
            }
        }

        $cuenta = [$item('Editar perfil', 'pi pi-user-edit', 'profile.show')];
        if ($user->currentTeam && $gate->allows('view', $user->currentTeam)) {
            $cuenta[] = ['label' => 'Equipo actual', 'icon' => 'pi pi-users', 'to' => 'teams.show', 'toParams' => ['team' => $user->current_team_id]];
        }

        return array_values(array_filter([
            ['label' => 'Inicio', 'items' => array_values(array_filter([
                $item('Tablero', 'pi pi-home', 'dashboard'),
                $allowed(['read solicitudes', 'read clientes']) ? $item('Mi trabajo', 'pi pi-check-square', 'solicitudes.trabajo') : null,
            ]))],
            $operacion ? ['label' => 'Operación', 'items' => $operacion] : null,
            $configuracion ? ['label' => 'Configuración', 'items' => $configuracion] : null,
            $administracion ? ['label' => 'Administración', 'items' => $administracion] : null,
            ['label' => 'Cuenta', 'items' => $cuenta],
        ]));
    }
}
