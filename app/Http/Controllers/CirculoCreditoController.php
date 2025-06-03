<?php

namespace App\Http\Controllers;

use App\Services\MenubarService;
use App\Services\SICs\CirculoDeCredito\FicoScorev2\FicoScorev2Service;
use App\Services\SICs\CirculoDeCredito\FintechScore\FintechScoreService;

use App\Services\SICs\CirculoDeCredito\FicoScorev2\FicoScorev2Repository;
use App\Services\SICs\CirculoDeCredito\FintechScore\FintechScoreRepository;

use Illuminate\Http\Request;
use Inertia\Inertia;

class CirculoCreditoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, MenubarService $menubarService)
    {
        return Inertia::render('HistorialCrediticio/CirculoDeCredito/Create', [
            'menubarItems' => $menubarService->getMenuItems($request),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $api = $request->input('api');
        $repository = null;
        $requestData = null;

        switch ($api) {
            case 'fico_score_v2':
                // {
                //     "folio": "123456",
                //     "persona": {
                //         "nombres": "JUAN",
                //         "apellidoPaterno": "SESENTAYDOS",
                //         "apellidoMaterno": "PRUEBA",
                //         "fechaNacimiento": "1965-08-09",
                //         "RFC": "SEPJ650809JG1",
                //         "domicilio": {
                //             "direccion": "PASADISO ENCONTRADO 58",
                //             "coloniaPoblacion": "MONTEVIDEO",
                //             "ciudad": "CIUDAD DE M\u00c9XICO",
                //             "CP": "07730",
                //             "delegacionMunicipio": "GUSTAVO A MADERO",
                //             "estado": "CDMX"
                //         }
                //     }
                // }
                $requestData = null;
                $repository = new FicoScorev2Repository(new FicoScorev2Service($requestData));
                break;
            case 'fintech':
                // {
                //     "folioOtorgante": "20210308",
                //     "persona": {
                //         "primerNombre": "SEBASTIAN",
                //         "apellidoPaterno": "PRUEBA",
                //         "apellidoMaterno": "HERNANDEZ",
                //         "fechaNacimiento": "1986-12-07",
                //         "RFC": "PUHS8612075KA",
                //         "domicilio": {
                //             "direccion": "ORIENTE 245 NO. 373 NO. 3",
                //             "coloniaPoblacion": "AGRICOLA ORIENTAL",
                //             "delegacionMunicipio": "IZTACALCO",
                //             "ciudad": "CIUDAD DE MEXICO",
                //             "estado": "CDMX",
                //             "CP": "08500",
                //             "pais": "MX"
                //         }
                //     }
                // }
                $requestData = null;
                $repository = new FintechScoreRepository(new FintechScoreService($requestData));
                break;
            default:
                break;
        }

        if ($repository == null) {
            return response()->redirectToRoute('historial-crediticio.index', [
                'error' => 'API no soportada'
            ]);
        }

        $repository->consultaScore();

        return response()->redirectToRoute('historial-crediticio.index', [
            'success' => 'Consulta realizada exitosamente',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
