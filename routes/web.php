<?php

use App\Http\Controllers\DashboardPdfController;
use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', Dashboard\Index::class)->name('dashboard');
    Route::get('dashboard/pdf/{period}', DashboardPdfController::class)
        ->whereIn('period', ['weekly', 'monthly'])
        ->name('dashboard.pdf');
});

require __DIR__.'/settings.php';
require __DIR__.'/fleet.php';
require __DIR__.'/maintenance.php';
require __DIR__.'/expenses.php';
require __DIR__.'/users.php';
