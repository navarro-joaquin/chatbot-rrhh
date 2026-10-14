<?php

namespace App\Http\Controllers\Api\Integracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Integracion\ListarSolicitudesRequest;
use App\Http\Resources\Api\Integracion\SolicitudCompensacionResource;
use App\Http\Resources\Api\Integracion\SolicitudVacacionResource;
use App\Models\SolicitudCompensacion;
use App\Models\SolicitudVacacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SolicitudController extends Controller
{
    public function vacaciones(ListarSolicitudesRequest $request): AnonymousResourceCollection
    {
        $solicitudes = $this->filtrar(SolicitudVacacion::query(), $request, 'fecha_inicio')
            ->paginate($request->integer('per_page', 50))
            ->withQueryString();

        return SolicitudVacacionResource::collection($solicitudes);
    }

    public function compensaciones(ListarSolicitudesRequest $request): AnonymousResourceCollection
    {
        $solicitudes = $this->filtrar(SolicitudCompensacion::query(), $request, 'fecha_compensacion')
            ->paginate($request->integer('per_page', 50))
            ->withQueryString();

        return SolicitudCompensacionResource::collection($solicitudes);
    }

    private function filtrar(Builder $query, ListarSolicitudesRequest $request, string $columnaFecha): Builder
    {
        return $query
            ->with('empleado:id,nombre_completo')
            ->whereHas('empleado')
            ->when($request->filled('empleado_id'), fn (Builder $query) => $query->where('empleado_id', $request->integer('empleado_id')))
            ->when($request->filled('estado'), fn (Builder $query) => $query->where('estado', $request->string('estado')->toString()))
            ->when($request->filled('desde'), fn (Builder $query) => $query->whereDate($columnaFecha, '>=', $request->string('desde')->toString()))
            ->when($request->filled('hasta'), fn (Builder $query) => $query->whereDate($columnaFecha, '<=', $request->string('hasta')->toString()))
            ->orderByDesc($columnaFecha)
            ->orderByDesc('id');
    }
}
