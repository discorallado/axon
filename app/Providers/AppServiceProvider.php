<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\Client;
use App\Models\Observers\ProjectObserver;
use App\Models\Observers\SubmissionItemObserver;
use App\Models\Observers\SubmissionRequestObserver;
use App\Models\Observers\TaskObserver;
use App\Models\Project;
use App\Models\SubmissionItem;
use App\Models\SubmissionRequest;
use App\Models\Task;
use App\Models\User;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        FilamentColor::register([
            'primary' => config('public-form.primary'),
        ]);

        Relation::enforceMorphMap([
            'user' => User::class,
            'submission_request' => SubmissionRequest::class,
            'submission_item' => SubmissionItem::class,
            'client' => Client::class,
            'project' => Project::class,
            'activity' => Activity::class,
            'task' => Task::class,
        ]);

        SubmissionRequest::observe(SubmissionRequestObserver::class);
        SubmissionItem::observe(SubmissionItemObserver::class);
        Project::observe(ProjectObserver::class);
        Task::observe(TaskObserver::class);

        RateLimiter::for('public-form', function (Request $request) {
            return Limit::perMinutes(10, 10000000)
                ->by($request->ip())
                ->response(function () {
                    return response()->view('public.forms.show', [
                        'errors' => collect([__('forms.public.throttle_error')]),
                    ], 429);
                });
        });
        // URL::forceHttps(true); // O puedes usar $this->app->isLocal() si prefieres forzarlo solo en local

    }
}
