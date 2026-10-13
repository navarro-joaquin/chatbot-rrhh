<?php

test('la pagina de inicio responde correctamente', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});
