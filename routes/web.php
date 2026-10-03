<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicBidController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicBidController::class, 'home'])->name('home');

Route::prefix('bids')->name('bids.')->group(function () {
    Route::get('open', [PublicBidController::class, 'open'])->name('open');
    Route::get('closed', [PublicBidController::class, 'closed'])->name('closed');
    Route::get('search', [PublicBidController::class, 'search'])->name('search');
    Route::get('{reference}', [PublicBidController::class, 'show'])
        ->where('reference', '[A-Za-z0-9._-]+')->name('show');
    Route::get('{reference}/documents/{document}', [PublicBidController::class, 'document'])
        ->where('reference', '[A-Za-z0-9._-]+')->name('documents.download');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'role:admin'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/', [StaffController::class, 'index'])->name('index');
    Route::get('/create', [StaffController::class, 'create'])->name('create');
    Route::post('/', [StaffController::class, 'store'])->name('store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
