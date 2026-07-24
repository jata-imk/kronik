<?php

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Relations\Relation;

test('legacy clientes morph alias resolves a direction owner', function () {
    $cliente = Cliente::factory()->create();

    expect($cliente->getMorphClass())->toBe('clientes')
        ->and(Relation::getMorphedModel('clientes'))->toBe(Cliente::class);
});
