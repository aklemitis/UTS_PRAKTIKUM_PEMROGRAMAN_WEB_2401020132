<?php

use App\Http\Controllers\PasienHewanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Paket 2 Tipe D - Sistem Pasien Klinik Hewan
Route::prefix('pasien-hewan')
    ->name('pasien-hewan.')
    ->controller(PasienHewanController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');                                   // Read + cari + filter
        Route::post('/', 'store')->name('store');                                  // Create
        Route::get('/{id}/edit', 'edit')->whereNumber('id')->name('edit');         // Form edit
        Route::put('/{id}', 'update')->whereNumber('id')->name('update');          // Update
        Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');     // Delete
    });
