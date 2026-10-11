<?php

use App\Http\Controllers\AiSearchController;
use App\Http\Controllers\CollaborationRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeveloperController;
use App\Http\Controllers\DeveloperProfileController;
use App\Http\Controllers\DevScoreController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\ProjectRoleController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\ResourceInteractionController;
use App\Http\Controllers\UserSkillController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

// Find developers + public developer profiles
Route::get('/devs', [DeveloperController::class, 'index'])->name('devs.index');
Route::get('/devs/{user}', [DeveloperController::class, 'show'])->name('devs.show');

Route::middleware('auth')->group(function () {
    // Account settings (Breeze): name, email, password, deletion
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Developer profile, completed after registration
    Route::get('/profile/developer', [DeveloperProfileController::class, 'edit'])->name('developer-profile.edit');
    Route::put('/profile/developer', [DeveloperProfileController::class, 'update'])->name('developer-profile.update');

    // Skills of the logged-in user
    Route::post('/profile/skills', [UserSkillController::class, 'store'])->name('profile.skills.store');
    Route::patch('/profile/skills/{skill}', [UserSkillController::class, 'update'])->name('profile.skills.update');
    Route::delete('/profile/skills/{skill}', [UserSkillController::class, 'destroy'])->name('profile.skills.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/devs/{user}/follow', [FollowController::class, 'store'])->name('devs.follow');
    Route::delete('/devs/{user}/follow', [FollowController::class, 'destroy'])->name('devs.unfollow');

    // Collaborative projects (registered before /projects/{project} so "create" is not read as a slug)
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    // Recruitment: roles the project is looking for
    Route::scopeBindings()->group(function () {
        Route::post('/projects/{project}/roles', [ProjectRoleController::class, 'store'])->name('projects.roles.store');
        Route::put('/projects/{project}/roles/{role}', [ProjectRoleController::class, 'update'])->name('projects.roles.update');
        Route::delete('/projects/{project}/roles/{role}', [ProjectRoleController::class, 'destroy'])->name('projects.roles.destroy');
    });

    // Collaboration requests (apply / invite) and answers
    Route::get('/collaborations', [CollaborationRequestController::class, 'index'])->name('collaborations.index');
    Route::post('/projects/{project}/requests', [CollaborationRequestController::class, 'store'])->name('projects.requests.store');
    Route::post('/projects/{project}/invitations', [CollaborationRequestController::class, 'invite'])->name('projects.invitations.store');
    Route::post('/collaborations/{collaborationRequest}/accept', [CollaborationRequestController::class, 'accept'])->name('collaborations.accept');
    Route::post('/collaborations/{collaborationRequest}/reject', [CollaborationRequestController::class, 'reject'])->name('collaborations.reject');
    Route::delete('/collaborations/{collaborationRequest}', [CollaborationRequestController::class, 'destroy'])->name('collaborations.destroy');

    // DevLink AI (members only, limited to 10 searches per minute)
    Route::get('/ai', [AiSearchController::class, 'create'])->name('ai.create');
    Route::post('/ai', [AiSearchController::class, 'store'])->middleware('throttle:10,1')->name('ai.store');
    Route::get('/ai/{aiSearch}', [AiSearchController::class, 'show'])->name('ai.show');

    // Dev Resources (registered before /resources/{resource} so "create" is not read as a slug)
    Route::get('/resources/create', [ResourceController::class, 'create'])->name('resources.create');
    Route::post('/resources', [ResourceController::class, 'store'])->name('resources.store');
    Route::get('/resources/{resource}/edit', [ResourceController::class, 'edit'])->name('resources.edit');
    Route::put('/resources/{resource}', [ResourceController::class, 'update'])->name('resources.update');
    Route::delete('/resources/{resource}', [ResourceController::class, 'destroy'])->name('resources.destroy');

    // Likes, ratings and comments
    Route::post('/resources/{resource}/like', [ResourceInteractionController::class, 'like'])->name('resources.like');
    Route::delete('/resources/{resource}/like', [ResourceInteractionController::class, 'unlike'])->name('resources.unlike');
    Route::post('/resources/{resource}/reviews', [ResourceInteractionController::class, 'review'])->name('resources.reviews.store');
    Route::delete('/resources/{resource}/reviews', [ResourceInteractionController::class, 'deleteReview'])->name('resources.reviews.destroy');
    Route::post('/resources/{resource}/comments', [ResourceInteractionController::class, 'comment'])->name('resources.comments.store');
    Route::delete('/comments/{comment}', [ResourceInteractionController::class, 'deleteComment'])->name('comments.destroy');

    // Favorites: developers, resources and projects
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites/{type}/{id}', [FavoriteController::class, 'store'])
        ->whereIn('type', array_keys(FavoriteController::TYPES))->whereNumber('id')->name('favorites.store');
    Route::delete('/favorites/{type}/{id}', [FavoriteController::class, 'destroy'])
        ->whereIn('type', array_keys(FavoriteController::TYPES))->whereNumber('id')->name('favorites.destroy');

    // My DevScore
    Route::get('/devscore', [DevScoreController::class, 'show'])->name('devscore.show');

    // Team members
    Route::patch('/projects/{project}/members/{member}', [ProjectMemberController::class, 'update'])->name('projects.members.update');
    Route::delete('/projects/{project}/members/{member}', [ProjectMemberController::class, 'destroy'])->name('projects.members.destroy');
    Route::post('/projects/{project}/leave', [ProjectMemberController::class, 'leave'])->name('projects.leave');
});

// African ranking (public)
Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');

// Public resource pages
Route::get('/resources', [ResourceController::class, 'index'])->name('resources.index');
Route::get('/resources/{resource}', [ResourceController::class, 'show'])->name('resources.show');
Route::get('/resources/{resource}/download', [ResourceController::class, 'download'])->name('resources.download');

// Public project pages
Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

require __DIR__.'/auth.php';
