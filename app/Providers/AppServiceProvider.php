<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\SubmissionAnswer;
use App\Models\SubmissionRequest;
use App\Models\User;
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
        // Morphmap explícito para evitar FQCNs en la DB
        Relation::enforceMorphMap([
            'user' => User::class,
            'submission_request' => SubmissionRequest::class,
            'submission_answer' => SubmissionAnswer::class,
            'comment' => Comment::class,
        ]);

        // Rate limiter para el formulario público
        RateLimiter::for('public-form', function (Request $request) {
            return Limit::perMinutes(10, 5)
                ->by($request->ip())
                ->response(function () {
                    return response()->view('public.forms.show', [
                        'errors' => collect([__('forms.public.throttle_error')]),
                    ], 429);
                });
        });
    }
}
