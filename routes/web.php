<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\BusStopImportController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\BusStopShowController;
use Illuminate\Support\Facades\Route;

// -----------------------------
// Public Routes
// -----------------------------
Route::get('/', function () {
    return view('welcome');
});

// -----------------------------
// Login Redirect Based on Role
// -----------------------------
Route::get('/redirect', function () {
    return auth()->user()->is_admin
        ? redirect()->route('admin.dashboard')
        : redirect()->route('dashboard');
})->middleware('auth');

// -----------------------------
// User Dashboard
// -----------------------------
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'user'])->name('dashboard');
    Route::get('/dashboard/search-stops', [DashboardController::class, 'searchStops'])->name('dashboard.searchStops');

    // Show multiple selected bus stops on map
    Route::get('/dashboard/map', [BusStopShowController::class, 'multiple'])->name('dashboard.map');
});

// -----------------------------
// Road Segment Speed API
// -----------------------------
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/api/road-speed', [BusStopShowController::class, 'getRoadSpeed']);
    Route::post('/api/road-speed', [BusStopShowController::class, 'saveRoadSegment']);
    Route::post('/api/road-segments-batch', [BusStopShowController::class, 'batchRoadSpeeds']);
});
Route::post('/api/save-road-segment', [BusStopShowController::class, 'saveRoadSegment'])
    ->middleware(['auth', 'verified']);
// -----------------------------
// Profile Routes
// -----------------------------
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// -----------------------------
// Admin Dashboard & Bus Stop Import
// -----------------------------
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    // Bus stop import form
    Route::get('/import-bus-stops', [BusStopImportController::class, 'showForm'])->name('admin.busstop.form');

    // API import
    Route::post('/import-bus-stops', [BusStopImportController::class, 'import'])->name('admin.busstop.import');

    // Excel import
    Route::post('/import-bus-stops/excel', [BusStopImportController::class, 'importExcel'])->name('admin.busstop.importExcel');
});

// -----------------------------
// Auth Routes
// -----------------------------
require __DIR__.'/auth.php';
