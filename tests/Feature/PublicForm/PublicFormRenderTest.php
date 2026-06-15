<?php

use App\Models\FormTemplate;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders solicitud page with status 200', function () {
    $org = Organization::factory()->create();
    FormTemplate::factory()->for($org, 'organization')->create([
        'slug' => 'tableros-electricos',
        'is_active' => true,
    ]);

    $this->get(route('solicitud.tableros'))->assertOk();
});

it('root url redirects to solicitud', function () {
    $this->get('/')->assertRedirectToRoute('solicitud.tableros');
});
