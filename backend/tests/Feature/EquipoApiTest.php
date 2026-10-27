<?php

use App\Models\Categoria;
use App\Models\Equipo;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/*
| TODO [B6] Escriba las tres pruebas. Cada una debe verificar el código
| de estado Y algo más (estructura, encabezado, mensaje o estado de la base).
|
| Quite el ->todo() de cada prueba al implementarla.
| Ejecute:  php artisan test --filter=EquipoApi
*/

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create());
});

it('crea un equipo y responde 201 con el encabezado Location', function () {
    $categoria = Categoria::factory()->create();
    $datos = Equipo::factory()->make(['categoria_id' => $categoria->id])->getAttributes();

    $respuesta = $this->postJson('/api/equipos', $datos);
    $respuesta->assertCreated();
    $equipo = Equipo::firstOrFail();

    $respuesta->assertHeader('Location', route('equipos.show', $equipo))
        ->assertJsonPath('id', $equipo->id)
        ->assertJsonPath('categoria.id', $categoria->id)
        ->assertJsonMissingPath('costo_interno');
    $this->assertDatabaseHas('equipos', ['codigo' => $datos['codigo']]);
});

it('rechaza un equipo con datos inválidos y responde 422 con errores por campo', function () {
    $categoria = Categoria::factory()->create();

    $this->postJson('/api/equipos', [
        'codigo' => 'EQ-12',
        'nombre' => 'Osciloscopio',
        'categoria_id' => $categoria->id,
        'fecha_adquisicion' => today()->toDateString(),
        'costo_interno' => 100,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['codigo'])
        ->assertJsonPath('errors.codigo.0', 'El código debe tener el formato EQ-0000.');
});

it('impide eliminar un equipo prestado y responde 409', function () {
    $equipo = Equipo::factory()->prestado()->create();

    $this->deleteJson("/api/equipos/{$equipo->id}")
        ->assertConflict()
        ->assertExactJson([
            'mensaje' => 'No se puede eliminar un equipo prestado.',
            'codigo' => 'REGLA_NEGOCIO',
        ]);
    $this->assertDatabaseHas('equipos', ['id' => $equipo->id]);
});

it('impide prestar un equipo en mantenimiento y responde 409', function () {
    $equipo = Equipo::factory()->enMantenimiento()->create();

    $this->postJson("/api/equipos/{$equipo->id}/prestamo")
        ->assertConflict()
        ->assertExactJson([
            'mensaje' => 'No se puede prestar un equipo en mantenimiento.',
            'codigo' => 'REGLA_NEGOCIO',
        ]);
    $this->assertDatabaseHas('equipos', ['id' => $equipo->id, 'estado' => 'mantenimiento']);
});

it('impide volver a prestar un equipo prestado y responde 409', function () {
    $equipo = Equipo::factory()->prestado()->create();

    $this->postJson("/api/equipos/{$equipo->id}/prestamo")
        ->assertConflict()
        ->assertExactJson([
            'mensaje' => 'El equipo ya se encuentra prestado.',
            'codigo' => 'REGLA_NEGOCIO',
        ]);
});

it('presta un equipo disponible y actualiza su estado', function () {
    $equipo = Equipo::factory()->create(['estado' => 'disponible']);

    $this->postJson("/api/equipos/{$equipo->id}/prestamo")
        ->assertOk()
        ->assertJsonPath('estado', 'prestado');
    $this->assertDatabaseHas('equipos', ['id' => $equipo->id, 'estado' => 'prestado']);
});
