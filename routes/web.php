<?php

use App\Http\Controllers\BidResponseController; 
use App\Http\Controllers\VendorResponseController;
use App\Http\Controllers\BidManageController; 
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicBidController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicBidController::class, 'home'])->name('home');

Route::prefix('bids')->name('bids.')->group(function () {
    Route::get('open', [PublicBidController::class, 'open'])->name('open');
    Route::get('closed', [PublicBidController::class, 'closed'])->name('closed');
    Route::get('search', [PublicBidController::class, 'search'])->name('search');
    Route::get('{reference}/respond', [VendorResponseController::class, 'create'])
        ->where('reference', '[A-Za-z0-9._-]+')->name('respond');
    Route::post('{reference}/respond', [VendorResponseController::class, 'store'])
        ->where('reference', '[A-Za-z0-9._-]+')->middleware('throttle:5,10')->name('respond.store');
    Route::get('{reference}/respond/received', [VendorResponseController::class, 'received'])
        ->where('reference', '[A-Za-z0-9._-]+')->name('respond.received');
    Route::get('{reference}', [PublicBidController::class, 'show'])
        ->where('reference', '[A-Za-z0-9._-]+')->name('show');
    Route::get('{reference}/documents/{document}', [PublicBidController::class, 'document'])
        ->where('reference', '[A-Za-z0-9._-]+')->name('documents.download');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'role:admin'])->prefix('staff')->name('staff.')->whereNumber('user')->group(function () {
    Route::get('/', [StaffController::class, 'index'])->name('index');
    Route::get('create', [StaffController::class, 'create'])->name('create');
    Route::post('/', [StaffController::class, 'store'])->name('store');
    Route::get('{user}/edit', [StaffController::class, 'edit'])->name('edit');
    Route::put('{user}', [StaffController::class, 'update'])->name('update');
    Route::post('{user}/disable', [StaffController::class, 'disable'])->name('disable');
    Route::post('{user}/enable', [StaffController::class, 'enable'])->name('enable');
    Route::delete('{user}', [StaffController::class, 'destroy'])->name('destroy');
    Route::post('{user}/restore', [StaffController::class, 'restore'])->withTrashed()->name('restore');
    Route::post('{user}/reset-link', [StaffController::class, 'sendResetLink'])->name('reset-link');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])
    ->prefix('manage/bids')
    ->name('manage.bids.')
    ->where(['bid' => '[A-Za-z0-9._-]+'])
    ->group(function () {
        Route::get('/', [BidManageController::class, 'index'])->name('index');
        Route::get('create', [BidManageController::class, 'create'])->name('create');
        Route::post('/', [BidManageController::class, 'store'])->name('store');
        Route::get('{bid}/edit', [BidManageController::class, 'edit'])->name('edit');
        Route::put('{bid}', [BidManageController::class, 'update'])->name('update');
        Route::delete('{bid}', [BidManageController::class, 'destroy'])->name('destroy');
        Route::get('{bid}/preview', [BidManageController::class, 'preview'])->name('preview');
        Route::post('{bid}/publish', [BidManageController::class, 'publish'])->name('publish');
        Route::post('{bid}/cancel', [BidManageController::class, 'cancel'])->name('cancel');
        Route::post('{bid}/award', [BidManageController::class, 'award'])->name('award');
        Route::post('{bid}/documents', [BidManageController::class, 'storeDocument'])->name('documents.store');
        Route::delete('{bid}/documents/{document}', [BidManageController::class, 'destroyDocument'])->name('documents.destroy');
        Route::get('{bid}/responses', [BidResponseController::class, 'index'])->name('responses.index');
        Route::get('{bid}/responses/create', [BidResponseController::class, 'create'])->name('responses.create');
        Route::post('{bid}/responses', [BidResponseController::class, 'store'])->name('responses.store');
        Route::get('{bid}/responses/{response}', [BidResponseController::class, 'show'])
            ->whereNumber('response')->name('responses.show');
        Route::get('{bid}/responses/{response}/files/{file}', [BidResponseController::class, 'file'])
            ->whereNumber(['response', 'file'])->name('responses.files.download');
    });

require __DIR__.'/auth.php';
