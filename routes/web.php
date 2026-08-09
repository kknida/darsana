<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use Livewire\Volt\Volt; 

/*
|--------------------------------------------------------------------------
| HALAMAN PUBLIK (Tanpa Login)
|--------------------------------------------------------------------------
*/

// Route::get('/', function () {
//     return view('traffic'); 
// })->name('traffic');

Route::get('/', [DashboardController::class, 'index'])->name('traffic'); 

Route::get('/finance', [FinanceController::class, 'index'])->name('finance');
Route::post('/finance/import', [FinanceController::class, 'import'])->name('finance.import');
Route::post('/finance/refresh', [FinanceController::class, 'refresh'])->name('finance.refresh');

use App\Http\Controllers\SapDashboardController;
Route::get('/sap-dashboard', [SapDashboardController::class, 'index'])->name('sap.dashboard');
Route::post('/sap/import', [SapDashboardController::class, 'import'])->name('sap.import');
Route::get('/api/budget-realisasi', [SapDashboardController::class, 'apiData'])->name('sap.api');

Route::get('/dashboard/last-update', function () {
    $log = \App\Models\ImportLog::latest('created_at')->first();
    return response()->json([
        'last_update'         => $log ? $log->created_at->toIso8601String() : null,
        'last_update_display' => $log ? $log->created_at->setTimezone('Asia/Jakarta')->format('H:i \\W\\I\\B') : '-',
    ]);
})->name('dashboard.last_update');
Route::get('/personnel', function () {
    return view('personnel');
})->name('personnel');

Route::get('/dashboard/finance', [FinanceController::class, 'index'])->name('dashboard.finance');
/*
|--------------------------------------------------------------------------
| HALAMAN ADMIN (Harus Login)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    
    // 1. Dashboard Overview
    // Menggunakan rute tunggal agar sinkron dengan sidebar request()->routeIs('dashboard')
    Route::get('/dashboard', [DashboardController::class, 'adminOverview'])->name('dashboard');

    // 2. Master Data Group
    Route::prefix('admin')->name('admin.')->group(function () {
        
        Volt::route('airlines', 'admin.airlines.index')->name('airlines.index');
        Volt::route('/', 'admin.index')->name('index');

        Volt::route('traffic', 'admin.traffic.index')->name('traffic.index');
        Volt::route('/', 'admin.index')->name('index');
        
        Volt::route('enroutes', 'admin.enroutes.index')->name('enroutes.index');
        Volt::route('/', 'admin.index')->name('index');

        Volt::route('terminals', 'admin.terminals.index')->name('terminals.index');
        Volt::route('/', 'admin.index')->name('index');

        Volt::route('finances', 'admin.finances.index')->name('finances.index');

        Route::get('sap-settings', [\App\Http\Controllers\Admin\SapSettingsController::class, 'index'])->name('sap-settings.index');
        Route::post('sap-settings', [\App\Http\Controllers\Admin\SapSettingsController::class, 'update'])->name('sap-settings.update');


    });

    // 3. User Profile Management
    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });
});

require __DIR__.'/auth.php';
