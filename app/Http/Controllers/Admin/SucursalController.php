<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityEvent;
use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SucursalController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('role_or_permission:Super Admin|read sucursales', only: ['index']),
            new Middleware('role_or_permission:Super Admin|create sucursales', only: ['store']),
            new Middleware('role_or_permission:Super Admin|update sucursales', only: ['update']),
            new Middleware('role_or_permission:Super Admin|delete sucursales', only: ['destroy']),
        ];
    }

    public function index()
    {
        return Inertia::render('Admin/Sucursales/Index', [
            'sucursales' => fn () => Sucursal::orderByDesc('activa')
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $fields = $this->validateSucursal($request);
        $sucursal = Sucursal::create($fields);

        $activityLog = app(ActivityLogService::class);
        $activityLog->log(
            event: ActivityEvent::BranchCreated,
            description: 'Sucursal creada',
            subject: $sucursal,
            metadata: ['changed_fields' => $activityLog->fieldNames($fields)],
            causer: Auth::user(),
        );

        return redirect()->back()->with('success', 'Sucursal creada');
    }

    public function update(Request $request, Sucursal $sucursal)
    {
        $fields = $this->validateSucursal($request, $sucursal);
        $sucursal->update($fields);

        $activityLog = app(ActivityLogService::class);
        $activityLog->log(
            event: ActivityEvent::BranchUpdated,
            description: 'Sucursal actualizada',
            subject: $sucursal,
            metadata: ['changed_fields' => $activityLog->fieldNames($fields)],
            causer: Auth::user(),
        );

        return redirect()->back()->with('success', 'Sucursal actualizada');
    }

    public function destroy(Sucursal $sucursal)
    {
        $sucursal->update(['activa' => false]);

        app(ActivityLogService::class)->log(
            event: ActivityEvent::BranchDeactivated,
            description: 'Sucursal desactivada',
            subject: $sucursal,
            metadata: ['changed_fields' => ['activa'], 'state' => 'inactiva'],
            causer: Auth::user(),
        );

        return redirect()->back()->with('success', 'Sucursal desactivada');
    }

    private function validateSucursal(Request $request, ?Sucursal $sucursal = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'clave' => [
                'required',
                'string',
                'max:20',
                Rule::unique('sucursales', 'clave')->ignore($sucursal),
            ],
            'domicilio' => ['nullable', 'array'],
            'domicilio.calle' => ['nullable', 'string', 'max:255'],
            'domicilio.numero_exterior' => ['nullable', 'string', 'max:50'],
            'domicilio.numero_interior' => ['nullable', 'string', 'max:50'],
            'domicilio.colonia' => ['nullable', 'string', 'max:127'],
            'domicilio.municipio' => ['nullable', 'string', 'max:127'],
            'domicilio.estado' => ['nullable', 'string', 'max:127'],
            'domicilio.codigo_postal' => ['nullable', 'string', 'regex:/^\d{5}$/'],
            'domicilio.pais_id' => ['nullable', 'integer', 'exists:paises,id'],
            'domicilio.pais_codigo_iso' => ['nullable', 'string', 'max:3'],
            'domicilio.codigo_postal_id' => [
                'nullable',
                'required_with:domicilio.codigo_postal',
                'integer',
                Rule::exists('codigos_postales', 'id')->where(
                    fn ($query) => $query
                        ->where('codigo', $request->input('domicilio.codigo_postal'))
                        ->where('division_admin_id', $request->input('domicilio.division_admin_tres_id')),
                ),
            ],
            'domicilio.division_admin_uno_id' => ['nullable', 'integer', 'exists:divisiones_administrativas,id'],
            'domicilio.division_admin_dos_id' => ['nullable', 'integer', 'exists:divisiones_administrativas,id'],
            'domicilio.division_admin_tres_id' => ['nullable', 'required_with:domicilio.codigo_postal', 'integer', 'exists:divisiones_administrativas,id'],
            'domicilio.pais' => ['nullable', 'string', 'max:127'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:127'],
            'horario' => ['nullable', 'array'],
            'horario.lunes_viernes' => ['nullable', 'string', 'max:80'],
            'horario.sabado' => ['nullable', 'string', 'max:80'],
            'horario.domingo' => ['nullable', 'string', 'max:80'],
            'prefijo_folio' => ['nullable', 'string', 'max:20'],
            'consecutivo_solicitud' => ['required', 'integer', 'min:1'],
            'consecutivo_contrato' => ['required', 'integer', 'min:1'],
            'consecutivo_credito' => ['required', 'integer', 'min:1'],
            'consecutivo_recibo' => ['required', 'integer', 'min:1'],
            'activa' => ['required', 'boolean'],
        ], [
            'clave.unique' => 'La clave de sucursal ya está en uso.',
        ]);
    }
}
