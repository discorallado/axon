<?php

use App\Enums\FormQuestionType;
use App\Models\FormQuestion;
use App\Models\FormSection;
use App\Models\FormTemplate;
use App\Models\Organization;
use App\Models\SubmissionRequest;
use App\Models\SubmissionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function makeTemplateWithQuestion(Organization $org): array
{
    $template = FormTemplate::factory()
        ->for($org, 'organization')
        ->create(['is_active' => true]);

    SubmissionStatus::factory()
        ->initial()
        ->for($org, 'organization')
        ->create();

    $section = FormSection::factory()->create([
        'organization_id' => $org->id,
        'form_template_id' => $template->id,
        'template_version' => 1,
    ]);

    $question = FormQuestion::factory()->create([
        'organization_id' => $org->id,
        'form_template_id' => $template->id,
        'form_section_id' => $section->id,
        'template_version' => 1,
        'type' => FormQuestionType::Text,
        'is_required' => true,
        'key' => 'potencia',
        'label' => 'Potencia (kW)',
    ]);

    return [$template, $question];
}

it('creates a submission with valid data', function () {
    Notification::fake();

    $org = Organization::factory()->create();
    [$template, $question] = makeTemplateWithQuestion($org);

    $response = $this->post(route('public.form.submit', $template->slug), [
        'submitter_name' => 'Juan Pérez',
        'submitter_email' => 'juan@example.com',
        '_hp' => '',
        '_ts' => time() - 10,
        'answers' => [$question->id => '100'],
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('submission_requests', [
        'submitter_email' => 'juan@example.com',
        'form_template_id' => $template->id,
    ]);

    $submission = SubmissionRequest::withoutGlobalScopes()->first();
    $this->assertStringStartsWith('SOL-', $submission->reference_code);
});

it('rejects submission when honeypot is filled', function () {
    $org = Organization::factory()->create();
    [$template] = makeTemplateWithQuestion($org);

    $this->post(route('public.form.submit', $template->slug), [
        'submitter_name' => 'Bot',
        'submitter_email' => 'bot@spam.com',
        '_hp' => 'filled_by_bot',
        '_ts' => time() - 10,
    ])->assertSessionHasErrors('_spam');

    $this->assertDatabaseEmpty('submission_requests');
});

it('rejects submission when form was submitted too fast', function () {
    $org = Organization::factory()->create();
    [$template] = makeTemplateWithQuestion($org);

    $this->post(route('public.form.submit', $template->slug), [
        'submitter_name' => 'Bot',
        'submitter_email' => 'bot@spam.com',
        '_hp' => '',
        '_ts' => time() - 1, // menos de 3 segundos
    ])->assertSessionHasErrors('_spam');
});

it('requires submitter name and email', function () {
    $org = Organization::factory()->create();
    [$template] = makeTemplateWithQuestion($org);

    $this->post(route('public.form.submit', $template->slug), [
        '_hp' => '',
        '_ts' => time() - 10,
    ])->assertSessionHasErrors(['submitter_name', 'submitter_email']);
});

it('rejects submission to inactive template', function () {
    $org = Organization::factory()->create();
    $template = FormTemplate::factory()
        ->for($org, 'organization')
        ->create(['is_active' => false]);

    $this->post(route('public.form.submit', $template->slug), [
        'submitter_name' => 'Test',
        'submitter_email' => 'test@example.com',
        '_hp' => '',
        '_ts' => time() - 10,
    ])->assertNotFound();
});
