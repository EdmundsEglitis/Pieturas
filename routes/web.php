<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\BusStopImportController;
use App\Http\Controllers\Admin\AdminDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ✅ LOGIN REDIRECT BASED ON ROLE
Route::get('/redirect', function () {
    if (auth()->user()->is_admin) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('dashboard');
})->middleware('auth');

// ✅ USER DASHBOARD
Route::get('/dashboard', [DashboardController::class, 'user'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/dashboard/search-stops', [DashboardController::class, 'searchStops'])->middleware(['auth', 'verified'])->name('dashboard.searchStops');

// ✅ PROFILE ROUTES
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ✅ ADMIN DASHBOARD
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    // Bus stop import form
    Route::get('/import-bus-stops', [BusStopImportController::class, 'showForm'])->name('admin.busstop.form');

    // API import
    Route::post('/import-bus-stops', [BusStopImportController::class, 'import'])->name('admin.busstop.import');

    // ✅ Excel import (new route)
    Route::post('/import-bus-stops/excel', [BusStopImportController::class, 'importExcel'])->name('admin.busstop.importExcel');
});

require __DIR__.'/auth.php';
