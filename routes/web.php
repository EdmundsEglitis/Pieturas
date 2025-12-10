<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\BusStopImportController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\BusStopShowController;
use App\Http\Controllers\RoadSegmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Login Redirect Based on Role
|--------------------------------------------------------------------------
*/
Route::get('/redirect', function () {
    return auth()->user()->is_admin
        ? redirect()->route('admin.dashboard')
        : redirect()->route('dashboard');
})->middleware('auth');

/*
|--------------------------------------------------------------------------
| User Dashboard
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'user'])
        ->name('dashboard');

    Route::get('/dashboard/search-stops', [DashboardController::class, 'searchStops'])
        ->name('dashboard.searchStops');

    // Map showing multiple bus stops
    Route::get('/dashboard/map', [BusStopShowController::class, 'multiple'])
        ->name('dashboard.map');
});

/*
|--------------------------------------------------------------------------
| Road Segment Speed API (AJAX endpoints)
|--------------------------------------------------------------------------
| ⚠️ These must NOT be duplicated. 
| ⚠️ Only authenticated + verified users can access.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // Get existing road speed from DB
    Route::get('/api/road-speed', [BusStopShowController::class, 'getRoadSpeed']);

    // Save a single road segment
    Route::post('/api/road-speed', [BusStopShowController::class, 'saveRoadSegment']);

    // Batch calculate speeds (API returns them)
    Route::post('/api/road-segments-batch', [BusStopShowController::class, 'batchRoadSpeeds']);

    // Batch save road segments (DB write)
    Route::post('/api/save-road-segment-batch', [BusStopShowController::class, 'saveRoadSegmentBatch']);
});

/*
|--------------------------------------------------------------------------
| Profile Management
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    Route::post('/api/route', [BusStopShowController::class, 'getRouteFromORS']);
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->group(function () {

    Route::get('/admin', [AdminDashboardController::class, 'index'])
        ->name('admin.dashboard');

    // Bus stop import menu
    Route::get('/import-bus-stops', [BusStopImportController::class, 'showForm'])
        ->name('admin.busstop.form');

    // Import via API
    Route::post('/import-bus-stops', [BusStopImportController::class, 'import'])
        ->name('admin.busstop.import');

    // Import via Excel
    Route::post('/import-bus-stops/excel', [BusStopImportController::class, 'importExcel'])
        ->name('admin.busstop.importExcel');

    Route::get('/road-segments', [RoadSegmentController::class, 'index'])->name('road-segments.index');
    Route::get('/road-segments/{roadSegment}/edit', [RoadSegmentController::class, 'edit'])->name('road-segments.edit');
    Route::patch('/road-segments/{roadSegment}', [RoadSegmentController::class, 'update'])->name('road-segments.update');

    // RENAMED route to avoid conflict with dashboard map
    Route::post('/api/save-road-segment-batch-admin', [RoadSegmentController::class, 'saveBatch']);
});

/*
|--------------------------------------------------------------------------
| Auth Scaffolding Routes
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
