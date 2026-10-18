<?php

use Djouamaah\Algeria\Http\Controllers\AlgeriaController;
use Illuminate\Support\Facades\Route;

Route::get('wilayas', [AlgeriaController::class, 'wilayas'])->name('wilayas');
Route::get('wilayas/{wilaya}', [AlgeriaController::class, 'wilaya'])->name('wilaya');
Route::get('wilayas/{wilaya}/dairas', [AlgeriaController::class, 'dairas'])->name('dairas');
Route::get('wilayas/{wilaya}/communes', [AlgeriaController::class, 'wilayaCommunes'])->name('wilaya.communes');
Route::get('dairas/{daira}/communes', [AlgeriaController::class, 'dairaCommunes'])->name('daira.communes');
Route::get('communes/{commune}/postal-codes', [AlgeriaController::class, 'postalCodes'])->name('commune.postal-codes');
Route::get('postal-codes/{code}', [AlgeriaController::class, 'postalCode'])->name('postal-code');
