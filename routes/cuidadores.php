<?php

use Illuminate\Support\Facades\Route;

Route::post('Cuidadores/login', [App\Http\Controllers\cuidadoresController::class, 'login']);
Route::post('Cuidadores/RecupearCuentaPCorreo', [App\Http\Controllers\cuidadoresController::class, 'recuperarCuentaPCorreo']);
Route::post('Cuidadores/VerificarCodigoRecuperacion', [App\Http\Controllers\cuidadoresController::class, 'verificarCodigoRecuperacion']);
