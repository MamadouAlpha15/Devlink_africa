<?php

namespace App\Providers;

use Anthropic\Client;
use App\Ai\ClaudeCriteriaExtractor;
use App\Ai\DeveloperMatcher;
use App\Ai\AfriCommitAi;
use App\Ai\KeywordCriteriaExtractor;
use App\Models\Badge;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Resource;
use App\Models\User;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // AfriCommit AI: Claude when an API key is configured, otherwise only the local analyser.
        $this->app->bind(ClaudeCriteriaExtractor::class, fn () => new ClaudeCriteriaExtractor(
            new Client(
                apiKey: config('services.anthropic.key'),
                requestOptions: [
                    // A slow answer falls back to the local analyser instead of blocking the page.
                    'transporter' => new GuzzleClient(['timeout' => 25, 'connect_timeout' => 5]),
                    'maxRetries' => 1,
                ],
            ),
            config('services.anthropic.model'),
        ));

        $this->app->bind(AfriCommitAi::class, fn ($app) => new AfriCommitAi(
            claude: config('services.anthropic.key') ? $app->make(ClaudeCriteriaExtractor::class) : null,
            keywords: $app->make(KeywordCriteriaExtractor::class),
            matcher: $app->make(DeveloperMatcher::class),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Short names stored in *_type columns (favorites, reports, activities, point_transactions).
        Relation::enforceMorphMap([
            'user' => User::class,
            'resource' => Resource::class,
            'project' => Project::class,
            'comment' => Comment::class,
            'badge' => Badge::class,
        ]);
    }
}
