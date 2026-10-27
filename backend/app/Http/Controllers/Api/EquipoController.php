<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarEquipoRequest;
use App\Http\Resources\EquipoResource;
use App\Models\Equipo;
use App\Services\EquipoService;
use Illuminate\Http\Request;

/**
 * TODO [B3] Controlador delgado: recibe, delega en el servicio y responde.
 *
 *  - Ningún método supera 15 líneas.
 *  - Ninguna regla de negocio vive aquí.
 *  - Nunca devuelva el modelo directo: use EquipoResource.
 *
 * Contrato que debe cumplir:
 *
 *  GET    /api/equipos?page=&per_page=&q=&estado=  200  colección paginada con data, links y meta
 *                                                       per_page por omisión 10, tope máximo 50
 *  GET    /api/equipos/{id}                        200  | 404
 *  POST   /api/equipos                             201 + encabezado Location | 422
 *  PUT    /api/equipos/{id}                        200  | 404 | 422
 *  DELETE /api/equipos/{id}                        204  | 404 | 409 (R1)
 *  POST   /api/equipos/{id}/prestamo               200  | 404 | 409 (R2)
 */
class EquipoController extends Controller
{
    public function __construct(private EquipoService $service) {}

    public function index(Request $request)
    {
        $porPagina = min(max($request->integer('per_page', 10), 1), 50);

        return EquipoResource::collection($this->service->listar(
            $request->query('q'), $request->query('estado'), $porPagina,
        ));
    }

    public function show(Equipo $equipo)
    {
        return response()->json((new EquipoResource($equipo->loadMissing('categoria')))->resolve());
    }

    public function store(GuardarEquipoRequest $request)
    {
        $equipo = $this->service->crear($request->validated());

        return response()->json((new EquipoResource($equipo))->resolve(), 201, [
            'Location' => route('equipos.show', $equipo),
        ]);
    }

    public function update(GuardarEquipoRequest $request, Equipo $equipo)
    {
        $equipo = $this->service->actualizar($equipo, $request->validated());

        return response()->json((new EquipoResource($equipo))->resolve());
    }

    public function destroy(Equipo $equipo)
    {
        $this->service->eliminar($equipo);

        return response()->noContent();
    }

    public function prestar(Equipo $equipo)
    {
        $equipo = $this->service->prestar($equipo);

        return response()->json((new EquipoResource($equipo))->resolve());
    }
}
