<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\ThreatIocController;
use App\Http\Controllers\WatchlistController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| HOME & DASHBOARD
|--------------------------------------------------------------------------
*/

Route::get('/', [DashboardController::class, 'index'])->name('home');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| SCANNER (HERO SECTION LINK & IOC CHECKER)
|--------------------------------------------------------------------------
*/

Route::post('/scan', [ScannerController::class, 'scan'])->name('scan');
Route::get('/api/samples', [ScannerController::class, 'samples'])->name('scanner.samples');
Route::get('/api/history', [ScannerController::class, 'history'])->name('scanner.history');

/*
|--------------------------------------------------------------------------
| THREAT IOC MANAGEMENT & FILTERED TABLES
|--------------------------------------------------------------------------
*/

Route::get('/threat-iocs', [ThreatIocController::class, 'index'])->name('threat-iocs.index');
Route::get('/threat-iocs/{threatIoc}', [ThreatIocController::class, 'show'])->name('threat-iocs.show');
Route::post('/threat-iocs', [ThreatIocController::class, 'store'])->name('threat-iocs.store');
Route::delete('/threat-iocs/{threatIoc}', [ThreatIocController::class, 'destroy'])->name('threat-iocs.destroy');

// Legacy alias for iocs route compatibility
Route::get('/iocs', [ThreatIocController::class, 'index'])->name('iocs.index');
Route::post('/iocs', [ThreatIocController::class, 'store'])->name('iocs.store');
Route::delete('/iocs/{threatIoc}', [ThreatIocController::class, 'destroy'])->name('iocs.destroy');

/*
|--------------------------------------------------------------------------
| WATCHLIST & WHITELIST MANAGEMENT
|--------------------------------------------------------------------------
*/

Route::get('/watchlists', [WatchlistController::class, 'index'])->name('watchlists.index');
Route::post('/watchlists', [WatchlistController::class, 'store'])->name('watchlists.store');
Route::put('/watchlists/{watchlist}', [WatchlistController::class, 'update'])->name('watchlists.update');
Route::post('/watchlists/{watchlist}/toggle', [WatchlistController::class, 'toggle'])->name('watchlists.toggle');
Route::delete('/watchlists/{watchlist}', [WatchlistController::class, 'destroy'])->name('watchlists.destroy');

/*
|--------------------------------------------------------------------------
| TWEETFEED API SYNC
|--------------------------------------------------------------------------
*/

Route::post('/sync', SyncController::class)->name('sync');

/*
|--------------------------------------------------------------------------
| EXPORT REPORTS
|--------------------------------------------------------------------------
*/

Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
Route::get('/reports/spss', [ReportController::class, 'spss'])->name('reports.spss');
Route::get('/reports/spss-syntax', [ReportController::class, 'spssSyntax'])->name('reports.spss-syntax');
Route::get('/reports/csv', [ReportController::class, 'csv'])->name('reports.csv');
