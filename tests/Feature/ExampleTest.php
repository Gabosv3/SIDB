<?php

test('the application returns a successful response', function () {
    // La raíz siempre redirige al panel administrativo (routes/web.php).
    $response = $this->get('/');

    $response->assertRedirect('/administrativo');
});
