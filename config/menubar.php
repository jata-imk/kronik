<?php

// Single catalogue of navigable screens; never include writes or JSON lookup endpoints.
return [
    'dashboard' => ['Inicio y cuenta', [
        'dashboard' => 'Panel principal', 'profile.show' => 'Mi perfil y seguridad', 'api-tokens.index' => 'Tokens API',
    ]],
    'clientes' => ['Clientes', [
        'clientes.index' => 'Listado de clientes', 'clientes.create' => 'Nuevo cliente',
        'clientes.show' => 'Información del cliente', 'clientes.edit' => 'Editar cliente',
        'clientes.expedientes.index' => 'Expedientes de clientes', 'clientes.expediente.show' => 'Abrir expediente',
        'solicitudes.create' => 'Nueva solicitud',
    ]],
    'solicitudes' => ['Solicitudes', [
        'solicitudes.index' => 'Listado de solicitudes', 'solicitudes.trabajo' => 'Mi trabajo',
        'solicitudes.create' => 'Nueva solicitud',
        'solicitudes.show' => 'Detalle de solicitud', 'solicitudes.edit' => 'Editar solicitud',
        'solicitudes.paquete.show' => 'Paquete contractual', 'solicitudes.desembolso.create' => 'Preparar desembolso QA',
    ]],
    'evaluacion-solicitudes' => ['Evaluación de solicitudes', ['evaluacion-solicitudes.index' => 'Bandeja de evaluación']],
    'cumplimiento' => ['Cumplimiento', ['cumplimiento.index' => 'Bandeja de cumplimiento']],
    'creditos' => ['Créditos y pagos', [
        'creditos.index' => 'Cartera de créditos', 'creditos.show' => 'Detalle del crédito',
        'creditos.pagos.create' => 'Registrar pago QA', 'creditos.anticipos.create' => 'Anticipo o liquidación QA',
        'creditos.pagos.show' => 'Recibo de pago',
    ]],
    'plantillas-notificaciones' => ['Notificaciones', ['notificaciones.index' => 'Plantillas globales de SMS y correo']],
    'comunicaciones' => ['Comunicaciones', ['comunicaciones.index' => 'Canales, reglas y envíos']],
    'productos-crediticios' => ['Productos crediticios', [
        'productos-crediticios.index' => 'Productos y simulador', 'originacion-politicas.show' => 'Política de originación',
        'productos-crediticios.calendario.index' => 'Calendario crediticio',
        'productos-crediticios.calendario.show' => 'Versión del calendario crediticio',
    ]],
    'plantillas-documentos' => ['Plantillas de documentos', ['plantillas-documentos.index' => 'Plantillas, versiones y recursos']],
    'documentos' => ['Documentos', [
        'clientes.expediente.show' => 'Documentos del cliente', 'documentos-generados.view' => 'Ver PDF generado',
    ]],
    'historial-crediticio' => ['Historial crediticio', [
        'clientes.historial-crediticio.index' => 'Listado de consultas', 'clientes.historial-crediticio.show' => 'Historial del cliente',
        'consultas-sic.show' => 'Reporte y gráficas SIC',
    ]],
    'circulo-credito' => ['Círculo de Crédito', [
        'circulo-credito.index' => 'Consulta SIC', 'circulo-credito.create' => 'Nueva consulta SIC',
    ]],
    'admin' => ['Administración', ['admin.dashboard' => 'Panel de administración']],
    'users' => ['Usuarios', ['admin.users.index' => 'Usuarios e invitaciones']],
    'teams' => ['Equipos', ['admin.teams.index' => 'Gestión de equipos', 'teams.show' => 'Centro del equipo']],
    'sucursales' => ['Sucursales', ['admin.sucursales.index' => 'Gestión de sucursales']],
    'roles' => ['Roles y permisos', ['admin.roles.index' => 'Gestión de roles y permisos']],
    'menubar-items' => ['Menú contextual', ['admin.menubar-items.index' => 'Menú contextual por módulos']],
    'activity-log' => ['Actividad y auditoría', ['admin.users.activity' => 'Actividad de usuarios']],
    'configuracion-empresa' => ['Empresa', ['admin.configuracion-empresa.index' => 'Configuración de empresa']],
];
