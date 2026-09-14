<?php

use function Pest\Laravel\get;

it('serves the player entry screen to guests', function () {
    get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Home'));
});

it('exposes a health endpoint', function () {
    get('/up')->assertOk();
});
