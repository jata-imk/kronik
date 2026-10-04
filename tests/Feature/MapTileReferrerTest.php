<?php

test('la página envía el origen a los mosaicos OSM de otro dominio', function () {
    $this->get('/')->assertOk()
        ->assertSee('<meta name="referrer" content="strict-origin-when-cross-origin">', false);
});
