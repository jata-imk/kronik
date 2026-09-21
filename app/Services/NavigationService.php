<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

class NavigationService
{
    public function forUser(User $user): array
    {
        $gate = Gate::forUser($user);
        $modules = [
            ['Clientes', 'pi pi-fw pi-users', 'clientes.index', ['read clientes']],
            ['Mi trabajo', 'pi pi-fw pi-check-square', 'solicitudes.trabajo', ['read solicitudes', 'read clientes']],
            ['Solicitudes', 'pi pi-fw pi-folder-open', 'solicitudes.index', ['read solicitudes', 'read clientes']],
            ['Consultas SIC', 'pi pi-fw pi-credit-card', 'clientes.historial-crediticio.index', ['read clientes', 'read historial-crediticio']],
            ['Productos crediticios', 'pi pi-fw pi-wallet', 'productos-crediticios.index', ['read productos-crediticios']],
            ['Documentos y plantillas', 'pi pi-fw pi-file-edit', 'plantillas-documentos.index', ['read plantillas-documentos']],
        ];
        $items = [];
        foreach ($modules as [$label, $icon, $to, $permissions]) {
            if ($gate->check($permissions)) {
                $items[] = compact('label', 'icon', 'to');
            }
        }
        $settings = [
            ['label' => 'Editar perfil', 'icon' => 'pi pi-fw pi-user-edit', 'to' => 'profile.show'],
        ];
        if ($user->currentTeam && $gate->allows('view', $user->currentTeam)) {
            $settings[] = ['label' => 'Equipo actual', 'icon' => 'pi pi-fw pi-users', 'to' => 'teams.show', 'toParams' => ['team' => $user->current_team_id]];
        }
        if ($gate->allows('access admin')) {
            $settings[] = ['label' => 'Administración', 'icon' => 'pi pi-fw pi-cog', 'to' => 'admin.dashboard'];
        }

        return array_values(array_filter([
            ['label' => 'Inicio', 'items' => [['label' => 'Tablero general', 'icon' => 'pi pi-fw pi-home', 'to' => 'dashboard']]],
            $items ? ['label' => 'Módulos', 'items' => $items] : null,
            ['label' => 'Configuraciones', 'items' => $settings],
        ]));
    }
}
