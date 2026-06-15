<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders solicitud page with status 200', function () {
    $this->get(route('solicitud.tableros'))->assertOk();
});

it('root url redirects to solicitud', function () {
    $this->get('/')->assertRedirectToRoute('solicitud.tableros');
});
