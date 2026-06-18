<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\SubmissionAnswer;
use App\Models\SubmissionItem;
use App\Models\SubmissionRequest;
use App\Models\User;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Registra el color primario globalmente para que el formulario público
        // lo reciba sin pasar por el middleware del panel de Filament
        FilamentColor::register([
            'primary' => config('public-form.primary'),
        ]);

        // Morphmap explícito para evitar FQCNs en la DB
        Relation::enforceMorphMap([
            'user' => User::class,
            'submission_request' => SubmissionRequest::class,
            'submission_answer' => SubmissionAnswer::class,
            'submission_item' => SubmissionItem::class,
            'comment' => Comment::class,
        ]);

        // Rate limiter para el formulario público
        RateLimiter::for('public-form', function (Request $request) {
            return Limit::perMinutes(10, 10000000)
                ->by($request->ip())
                ->response(function () {
                    return response()->view('public.forms.show', [
                        'errors' => collect([__('forms.public.throttle_error')]),
                    ], 429);
                });
        });
    }
}
