<?php

use App\Http\Controllers\DataUploadController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::post('uploads/nodes', [DataUploadController::class, 'uploadNodes'])->name('uploads.nodes');
    Route::post('uploads/edges', [DataUploadController::class, 'uploadEdges'])->name('uploads.edges');
});

require __DIR__.'/settings.php';
