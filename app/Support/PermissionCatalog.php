<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class PermissionCatalog
{
    private const MODULES = [
        'dashboard' => 'Tablero', 'admin' => 'Administración', 'users' => 'Usuarios',
        'roles' => 'Roles', 'menubar-items' => 'Menú de navegación', 'activity-log' => 'Bitácora de actividad',
        'configuracion-empresa' => 'Configuración de empresa', 'sucursales' => 'Sucursales',
        'clientes' => 'Clientes', 'productos-crediticios' => 'Productos crediticios',
        'plantillas-documentos' => 'Plantillas documentales', 'documentos' => 'Documentos',
        'historial-crediticio' => 'Historial crediticio', 'circulo-credito' => 'Consultas SIC',
        'teams' => 'Equipos', 'solicitudes' => 'Solicitudes',
        'evaluacion-solicitudes' => 'Evaluación de solicitudes', 'cumplimiento' => 'Cumplimiento',
        'creditos' => 'Créditos y pagos',
    ];

    private const ACTIONS = [
        'create' => 'Crear', 'read' => 'Consultar', 'update' => 'Editar', 'delete' => 'Eliminar o desactivar',
        'access' => 'Acceder a', 'transfer' => 'Transferir', 'activate' => 'Activar', 'retire' => 'Retirar',
        'version' => 'Crear versiones de', 'simulate' => 'Simular', 'manage commissions' => 'Administrar comisiones de',
        'manage origination' => 'Configurar originación de', 'generate' => 'Generar', 'download' => 'Descargar',
        'reconcile' => 'Conciliar intentos de', 'add members' => 'Agregar integrantes a',
        'remove members' => 'Quitar integrantes de', 'update members' => 'Editar integrantes de',
        'assign' => 'Asignar responsable de', 'review' => 'Revisar', 'cancel' => 'Cancelar', 'approve' => 'Aprobar',
        'prepare package' => 'Preparar paquete de', 'receive signature' => 'Recibir firmas de',
        'review signature' => 'Revisar firmas de', 'disburse' => 'Registrar desembolso de',
        'pay' => 'Registrar pagos de', 'reverse payments' => 'Revertir pagos de',
        'prepay' => 'Registrar anticipos de', 'settle' => 'Registrar liquidación de',
    ];

    public static function module(string $name): array
    {
        $label = self::MODULES[$name] ?? 'Módulo personalizado';

        return ['etiqueta' => $label, 'descripcion' => "Funciones de {$label}. Las operaciones disponibles dependen de los permisos asignados."];
    }

    public static function permission(string $name): array
    {
        $resource = str($name)->afterLast(' ')->toString();
        $action = substr($name, 0, -(strlen($resource) + 1));
        $label = isset(self::MODULES[$resource], self::ACTIONS[$action])
            ? self::ACTIONS[$action].' '.mb_strtolower(self::MODULES[$resource])
            : 'Permiso personalizado';
        $description = $name === 'read clientes'
            ? 'Consultar el listado y el expediente de clientes. El alcance de los datos lo determina la política de acceso de clientes.'
            : "Autoriza la acción «{$label}». Se aplican además las políticas, estados y restricciones de cada operación.";

        return ['etiqueta' => $label, 'descripcion' => $description];
    }

    public static function fill(Model $model, bool $permission = false): void
    {
        // Historical migrations create catalogue records before adding these columns.
        if (! Schema::hasColumn($model->getTable(), 'etiqueta')) {
            return;
        }
        foreach ($permission ? self::permission($model->name) : self::module($model->name) as $key => $value) {
            if (! $model->getAttribute($key)) {
                $model->setAttribute($key, $value);
            }
        }
    }
}
