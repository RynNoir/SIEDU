<?php

it('redirects guests to login from the root url', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});
