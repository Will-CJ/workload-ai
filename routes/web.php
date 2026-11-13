<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
    Route::livewire('import', 'pages::import')->name('import');
    Route::livewire('activities', 'pages::activities')->name('activities');
    Route::livewire('groups', 'pages::groups')->name('groups');
    Route::livewire('departments', 'pages::departments.index')->name('departments.index');
    Route::livewire('departments/{department}', 'pages::departments.show')->name('departments.show');
    Route::livewire('employees/{employee}', 'pages::employees.show')->name('employees.show');
});

require __DIR__.'/settings.php';
