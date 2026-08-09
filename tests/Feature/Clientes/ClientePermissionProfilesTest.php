<?php

use App\Models\Cliente;
use App\Models\ClienteDocumento;
use App\Models\User;
use Database\Seeders\DevelopmentSeeder;
use Database\Seeders\SystemSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(SystemSeeder::class);
    $this->seed(DevelopmentSeeder::class);
});

test('development users have the expected team roles and client permissions', function () {
    $owner = User::where('email', 'test@example.com')->firstOrFail();
    $team = $owner->currentTeam;
    setPermissionsTeamId($team->id);

    $reader = User::where('email', 'consulta.clientes@example.test')->firstOrFail();
    $editor = User::where('email', 'editor.expedientes@example.test')->firstOrFail();
    $denied = User::where('email', 'sin.acceso.clientes@example.test')->firstOrFail();

    setPermissionsTeamId($editor->current_team_id);
    expect($editor->can('update clientes'))->toBeTrue();

    expect($reader->current_team_id)->toBe($team->id)
        ->and($editor->current_team_id)->toBe($team->id)
        ->and($denied->current_team_id)->toBe($team->id)
        ->and($team->users()->whereKey($reader->id)->exists())->toBeTrue()
        ->and($team->users()->whereKey($editor->id)->exists())->toBeTrue()
        ->and($team->users()->whereKey($denied->id)->exists())->toBeTrue()
        ->and($reader->getRoleNames()->all())->toBe(['Consulta de clientes'])
        ->and($reader->getAllPermissions()->pluck('name')->sort()->values()->all())->toBe(['read clientes'])
        ->and($editor->getRoleNames()->all())->toBe(['Edición de expedientes'])
        ->and($editor->getAllPermissions()->pluck('name')->sort()->values()->all())->toBe(['read clientes', 'update clientes'])
        ->and($denied->getRoleNames()->all())->toBe(['Sin acceso a clientes'])
        ->and($denied->getAllPermissions()->pluck('name')->all())->toBe([])
        ->and($owner->hasRole('Super Admin'))->toBeTrue();
});

test('development permission profiles enforce client dossier access', function () {
    Storage::fake('local');

    $cliente = Cliente::firstOrFail();
    $documento = ClienteDocumento::where('cliente_id', $cliente->id)->firstOrFail();
    $path = "clientes/{$cliente->id}/documentos/demo.pdf";
    Storage::disk('local')->put($path, 'documento demo');
    $documento->update([
        'disk' => 'local',
        'path' => $path,
        'nombre_original' => 'demo.pdf',
        'mime_type' => 'application/pdf',
        'tamano_bytes' => 14,
        'estado' => 'recibido',
    ]);

    $reader = User::where('email', 'consulta.clientes@example.test')->firstOrFail();
    $editor = User::where('email', 'editor.expedientes@example.test')->firstOrFail();
    $denied = User::where('email', 'sin.acceso.clientes@example.test')->firstOrFail();

    $this->actingAs($reader)
        ->get(route('clientes.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('can.create', false)
            ->where('can.update', false)
            ->where('can.delete', false)
            ->has('clientes.0.relaciones_count'));

    $this->actingAs($reader)
        ->get(route('clientes.expediente.show', $cliente))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('can.update', false));

    $this->actingAs($reader)
        ->get(route('clientes.documentos.download', [$cliente, $documento]))
        ->assertOk();

    $this->actingAs($reader)
        ->patch(route('clientes.expediente.perfil.update', $cliente), [
            'ocupacion' => 'Sin cambios',
            'actividad_economica' => 'Sin cambios',
            'ingresos_mensuales' => 1000,
            'egresos_mensuales' => 500,
            'origen_recursos' => 'Sin cambios',
        ])
        ->assertForbidden();

    $this->flushSession();

    $this->actingAs($editor)
        ->from(route('clientes.expediente.show', $cliente))
        ->patch(route('clientes.expediente.perfil.update', $cliente), [
            'ocupacion' => 'Editora autorizada',
            'actividad_economica' => 'Servicios',
            'ingresos_mensuales' => 30000,
            'egresos_mensuales' => 12000,
            'origen_recursos' => 'Actividad profesional',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('clientes.expediente.show', $cliente));

    $this->assertDatabaseHas('clientes', [
        'id' => $cliente->id,
        'ocupacion' => 'Editora autorizada',
    ]);

    $this->flushSession();

    $this->actingAs($denied)
        ->get(route('clientes.expediente.show', $cliente))
        ->assertForbidden();
});
