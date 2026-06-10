<?php

use App\Models\FormSection;
use App\Models\FormTemplate;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders public form for active template', function () {
    $org = Organization::factory()->create();
    $template = FormTemplate::factory()
        ->for($org, 'organization')
        ->create(['is_active' => true]);

    FormSection::factory()->create([
        'organization_id' => $org->id,
        'form_template_id' => $template->id,
        'template_version' => 1,
        'title' => 'Datos generales',
    ]);

    $response = $this->get(route('public.form.show', $template->slug));

    $response->assertOk();
    $response->assertSee($template->name);
    $response->assertSee('Datos generales');
});

it('returns 404 for inactive template', function () {
    $org = Organization::factory()->create();
    $template = FormTemplate::factory()
        ->for($org, 'organization')
        ->create(['is_active' => false]);

    $this->get(route('public.form.show', $template->slug))
        ->assertNotFound();
});

it('returns 404 for unknown slug', function () {
    $this->get(route('public.form.show', 'slug-que-no-existe'))
        ->assertNotFound();
});

it('renders conditional rules as JSON in the page', function () {
    $org = Organization::factory()->create();
    $template = FormTemplate::factory()
        ->for($org, 'organization')
        ->create(['is_active' => true]);

    $response = $this->get(route('public.form.show', $template->slug));

    $response->assertOk();
    $response->assertSee('window.__formRules', false);
});
