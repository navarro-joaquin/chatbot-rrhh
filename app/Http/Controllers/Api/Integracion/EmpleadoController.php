<?php

namespace App\Http\Controllers\Api\Integracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Integracion\ListarEmpleadosRequest;
use App\Http\Resources\Api\Integracion\EmpleadoResource;
use App\Models\Empleado;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmpleadoController extends Controller
{
    public function index(ListarEmpleadosRequest $request): AnonymousResourceCollection
    {
        $empleados = Empleado::query()
            ->with('contratoVigente')
            ->when($request->filled('estado'), fn ($query) => $query->where('estado', $request->boolean('estado')))
            ->orderBy('nombre_completo')
            ->orderBy('id')
            ->paginate($request->integer('per_page', 50))
            ->withQueryString();

        return EmpleadoResource::collection($empleados);
    }

    public function show(int $empleado): EmpleadoResource
    {
        return new EmpleadoResource(
            Empleado::query()->with('contratoVigente')->findOrFail($empleado)
        );
    }
}
