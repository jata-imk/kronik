<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Sic;
use App\Models\SicApi;
use App\Models\SicQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class HistorialCrediticioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Cliente::class);
        $clientesCount = Cliente::count();
        $clientesUntilLastMonth = Cliente::where('created_at', '<=', now()->subMonth())->count();

        $clientesWithSicQueryCount = SicQuery::distinct('cliente_id')->count('cliente_id');
        $clientesWithSicQueryUntilLastMonthCount = SicQuery::where('fecha_consulta', '<=', now()->subMonth())->distinct('cliente_id')->count('cliente_id');

        $sics = Sic::all();

        $sicsQueriesCount = SicQuery::count();
        $sicsQueriesCountUntilLastMonth = SicQuery::where('fecha_consulta', '<=', now()->subMonth())->count();

        $sicApis = SicApi::all();
        $sicQueries = SicQuery::with(['sic', 'api', 'cliente'])
            ->orderBy('fecha_consulta', 'desc')
            ->paginate(5, ['id', 'cliente_id', 'sic_id', 'sic_api_id', 'fecha_consulta', 'status', 'mensaje_error', 'response_data']);

        return Inertia::render('HistorialCrediticio/Index', [
            'clientesCount' => $clientesCount,
            'clientesUntilLastMonth' => $clientesUntilLastMonth,
            'clientesWithSicQueryCount' => $clientesWithSicQueryCount,
            'clientesWithSicQueryUntilLastMonthCount' => $clientesWithSicQueryUntilLastMonthCount,
            'sics' => $sics,
            'sicsQueriesCount' => $sicsQueriesCount,
            'sicsQueriesCountUntilLastMonth' => $sicsQueriesCountUntilLastMonth,
            'sicApis' => $sicApis,
            'sicQueriesPaginated' => $sicQueries,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Cliente $cliente)
    {
        Gate::authorize('view', $cliente);
        $sics = Sic::all();

        $sicsQueries = SicQuery::where('cliente_id', $cliente->id)->get([
            'id',
            'cliente_id',
            'sic_id',
            'sic_api_id',
            'fecha_consulta',
            'status',
        ]);

        $lastSicQuery = $sicsQueries->last();
        if ($lastSicQuery) {
            $lastSicQuery = SicQuery::where('id', $lastSicQuery->id)->get([
                'id',
                'cliente_id',
                'sic_id',
                'sic_api_id',
                'fecha_consulta',
                'status',
                'response_data',
            ])->first();
        }

        $antepenultimateSicQuery = SicQuery::where('cliente_id', $cliente->id)
            ->where('id', '<', $lastSicQuery->id ?? 0)
            ->orderBy('fecha_consulta', 'desc')
            ->get([
                'id',
                'cliente_id',
                'sic_id',
                'sic_api_id',
                'fecha_consulta',
                'status',
                'response_data',
            ])
            ->first();

        return Inertia::render('HistorialCrediticio/Show', [
            'sics' => $sics,
            'cliente' => $cliente,
            'sicsQueries' => $sicsQueries,
            'lastSicQuery' => $lastSicQuery,
            'antepenultimateSicQuery' => $antepenultimateSicQuery,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
