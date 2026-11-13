<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExportController;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
    Route::livewire('import', 'pages::import')->name('import');
    Route::livewire('activities', 'pages::activities')->name('activities');
    Route::livewire('groups', 'pages::groups')->name('groups');
    Route::livewire('departments', 'pages::departments.index')->name('departments.index');
    Route::livewire('departments/{department}', 'pages::departments.show')->name('departments.show');
    Route::livewire('employees/{employee}', 'pages::employees.show')->name('employees.show');

    Route::livewire('reports', 'pages::reports')->name('reports');

    Route::controller(ExportController::class)->prefix('exports')->name('exports.')->group(function () {
        Route::get('pdf', 'pdf')->name('pdf');
        Route::get('excel', 'excel')->name('excel');
        Route::get('results-json', 'json')->name('json');
    });
});

require __DIR__.'/settings.php';
