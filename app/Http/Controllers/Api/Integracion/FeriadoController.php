<?php

namespace App\Http\Controllers\Api\Integracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Integracion\ListarFeriadosRequest;
use App\Http\Resources\Api\Integracion\FeriadoResource;
use App\Models\Feriado;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeriadoController extends Controller
{
    public function index(ListarFeriadosRequest $request): AnonymousResourceCollection
    {
        $feriados = Feriado::query()
            ->with('gestion')
            ->when($request->filled('gestion'), fn ($query) => $query->whereHas(
                'gestion',
                fn ($gestion) => $gestion->where('anio', $request->integer('gestion'))
            ))
            ->when($request->filled('estado'), fn ($query) => $query->where('estado', $request->boolean('estado')))
            ->orderBy('fecha')
            ->get();

        return FeriadoResource::collection($feriados);
    }
}
