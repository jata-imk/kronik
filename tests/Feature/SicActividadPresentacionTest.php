<?php

use App\Models\SicEvento;
use App\Services\SICs\SicOperacionService;
use Inertia\Testing\AssertableInertia as Assert;

test('detalle SIC muestra nombres y distingue iniciador de ejecución automática sin exponer datos de cuenta', function () {
    [$actor, $cliente, $data] = sicQaFixture();
    $query = app(SicOperacionService::class)->preparar($cliente, $data, $actor);
    SicEvento::create(['sic_query_id' => $query->id, 'estado' => 'enviando', 'actor_id' => $actor->id]);
    SicEvento::create(['sic_query_id' => $query->id, 'estado' => 'conciliada_sin_resultado', 'actor_id' => $actor->id]);
    SicEvento::create(['sic_query_id' => $query->id, 'estado' => 'incierta', 'actor_id' => null]);

    $this->actingAs($actor)->get(route('consultas-sic.show', $query))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('eventos.0.actor', ['name' => $actor->name])->where('eventos.0.automatico', false)
            ->where('eventos.1.actor', ['name' => $actor->name])->where('eventos.1.automatico', true)
            ->where('eventos.2.automatico', false)
            ->where('eventos.3.actor', null)->where('eventos.3.automatico', true)
            ->missing('eventos.0.actor.email')->missing('eventos.0.actor.profile_photo_url')
            ->missing('eventos.0.actor_id'));
});
