<?php

namespace App\Providers;

use App\Models\Badge;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
