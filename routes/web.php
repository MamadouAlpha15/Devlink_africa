<?php

use App\Http\Controllers\CollaborationRequestController;
use App\Http\Controllers\DeveloperController;
use App\Http\Controllers\DeveloperProfileController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\ProjectRoleController;
use App\Http\Controllers\UserSkillController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

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

    // Team members
    Route::patch('/projects/{project}/members/{member}', [ProjectMemberController::class, 'update'])->name('projects.members.update');
    Route::delete('/projects/{project}/members/{member}', [ProjectMemberController::class, 'destroy'])->name('projects.members.destroy');
    Route::post('/projects/{project}/leave', [ProjectMemberController::class, 'leave'])->name('projects.leave');
});

// Public project pages
Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

require __DIR__.'/auth.php';
