<?php

namespace App\Http\Controllers;

use App\Models\SicQuery;
use App\Services\MenubarService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HistorialCrediticioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, MenubarService $menubarService)
    {
        $sicsQueries = SicQuery::where('cliente_id', 8)->get();
        // echo '<pre>';
        // die(var_dump($menubarService->getMenuItems($request)));

        return Inertia::render('HistorialCrediticio/Index', [
            'menubarItems' => $menubarService->getMenuItems($request),
            'sicsQueries' => $sicsQueries
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
