<?php

use App\Http\Controllers\DeveloperController;
use App\Http\Controllers\DeveloperProfileController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserSkillController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Public developer profiles
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
});

require __DIR__.'/auth.php';
