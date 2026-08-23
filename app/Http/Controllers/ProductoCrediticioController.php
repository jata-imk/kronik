<?php

namespace App\Http\Controllers;

use App\Enums\ActivityEvent;
use App\Enums\MetodoAmortizacion;
use App\Enums\PeriodicidadCredito;
use App\Http\Requests\ActivarProductoVersionRequest;
use App\Http\Requests\GuardarProductoRequest;
use App\Http\Requests\SimularCreditoRequest;
use App\Models\ConceptoComision;
use App\Models\ProductoCrediticio;
use App\Models\ProductoVersion;
use App\Services\ActivityLogService;
use App\Services\Credito\SimuladorCreditoSimple;
use App\Services\FechaEmpresa;
use App\Services\ProductoVersionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProductoCrediticioController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:read productos-crediticios', only: ['index']),
            new Middleware('permission:create productos-crediticios', only: ['store']),
            new Middleware('permission:update productos-crediticios', only: ['update']),
            new Middleware('permission:version productos-crediticios', only: ['versionar']),
            new Middleware('permission:activate productos-crediticios', only: ['activar']),
            new Middleware('permission:retire productos-crediticios', only: ['retirar']),
            new Middleware('permission:simulate productos-crediticios', only: ['simular']),
        ];
    }

    public function index(FechaEmpresa $fechaEmpresa)
    {
        $this->authorize('viewAny', ProductoCrediticio::class);

        return Inertia::render('ProductosCrediticios/Index', [
            'productos' => fn () => ProductoCrediticio::query()->with(['versiones' => fn ($query) => $query->with(['periodicidades', 'reglas', 'comisiones.concepto'])->withCount('usos')->orderByDesc('numero')])->orderBy('nombre')->get(),
            'conceptosComision' => fn () => ConceptoComision::query()->where('activo', true)->orderBy('nombre')->get(),
            'simuladorDefaults' => fn () => [
                'fecha_disposicion' => $fechaEmpresa->hoy()->toDateString(),
            ],
            'activacionDefaults' => fn () => [
                'hoy' => $fechaEmpresa->hoy()->toDateString(),
                'zona_horaria' => $fechaEmpresa->zonaHoraria(),
            ],
        ]);
    }

    public function store(GuardarProductoRequest $request, ProductoVersionService $service)
    {
        $this->authorize('create', ProductoCrediticio::class);
        $producto = $service->crear($request->validated(), Auth::id());
        $this->actividad(ActivityEvent::CreditProductCreated, 'Producto crediticio creado', $producto, ['clave', 'nombre']);

        return back()->with('success', 'Producto creado como borrador.');
    }

    public function update(GuardarProductoRequest $request, ProductoCrediticio $producto, ProductoVersion $version, ProductoVersionService $service)
    {
        $this->authorize('update', $producto);
        abort_unless($version->producto_crediticio_id === $producto->id, 404);
        $service->actualizar($producto, $version, $request->validated());
        $this->actividad(ActivityEvent::CreditProductUpdated, 'Borrador de producto actualizado', $producto, ['configuracion_comercial', 'reglas_calculo']);

        return back()->with('success', 'Borrador actualizado.');
    }

    public function versionar(ProductoCrediticio $producto, ProductoVersion $version, ProductoVersionService $service)
    {
        $this->authorize('version', $producto);
        abort_unless($version->producto_crediticio_id === $producto->id, 404);
        $nueva = $service->nuevaVersion($producto, $version, Auth::id());
        $this->actividad(ActivityEvent::CreditProductVersioned, 'Nueva versión de producto creada', $producto, ['version']);

        return back()->with('success', "Versión {$nueva->numero} creada como borrador.");
    }

    public function activar(ActivarProductoVersionRequest $request, ProductoVersion $version, ProductoVersionService $service)
    {
        $this->authorize('activate', $version);
        $data = $request->validated();
        $version = $service->activar($version, $data['vigente_desde']);
        $this->actividad(ActivityEvent::CreditProductActivated, 'Versión de producto activada o programada', $version->producto, ['estado', 'vigente_desde']);

        return back()->with('success', $version->estado->value === 'activa' ? 'Versión activada.' : 'Activación programada.');
    }

    public function retirar(ProductoVersion $version, ProductoVersionService $service)
    {
        $this->authorize('retire', $version);
        $service->retirar($version);
        $this->actividad(ActivityEvent::CreditProductRetired, 'Versión de producto retirada', $version->producto, ['estado']);

        return back()->with('success', 'Versión retirada; su historial se conserva.');
    }

    public function simular(SimularCreditoRequest $request, ProductoVersion $version, SimuladorCreditoSimple $simulador)
    {
        $this->authorize('simulate', $version);
        $data = $request->validated();

        $incluirFormula = app()->environment('local') || (bool) $request->user()?->is_super_admin;

        return response()->json($simulador->simular(
            $version,
            $data['monto'],
            PeriodicidadCredito::from($data['periodicidad']),
            (int) $data['plazo'],
            MetodoAmortizacion::from($data['metodo']),
            CarbonImmutable::parse($data['fecha']),
            $data['comisiones_opcionales'] ?? [],
            $incluirFormula,
        ));
    }

    private function actividad(ActivityEvent $event, string $description, ProductoCrediticio $producto, array $campos): void
    {
        app(ActivityLogService::class)->log($event, $description, $producto, ['changed_fields' => $campos, 'product' => $producto->clave], Auth::user());
    }
}
