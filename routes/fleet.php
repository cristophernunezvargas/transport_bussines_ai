<?php

use App\Livewire\Customers;
use App\Livewire\Drivers;
use App\Livewire\Profitability;
use App\Livewire\Routes as RouteScreens;
use App\Livewire\Trips;
use App\Livewire\Trucks;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('profitability', Profitability\Index::class)->name('profitability.index');
    Route::livewire('trucks', Trucks\Index::class)->name('trucks.index');
    Route::livewire('trucks/{truck}/costs', Trucks\Costs::class)->name('trucks.costs');
    Route::livewire('drivers', Drivers\Index::class)->name('drivers.index');
    Route::livewire('customers', Customers\Index::class)->name('customers.index');
    Route::livewire('routes', RouteScreens\Index::class)->name('routes.index');
    Route::livewire('trips', Trips\Index::class)->name('trips.index');
});
