<?php

use App\Http\Controllers\familiaresController;
use Illuminate\Support\Facades\Route;

Route::post('Familiares/Registro',[familiaresController::class,'registro']);
Route::get('Familiares/Verificacion/{CorreoE}/{CodigoVerificacion}',[familiaresController::class,'verificacion']);
Route::post('Familiares/Login',[familiaresController::class,'login']);
Route::post('Familiares/RecupearCuentaPCorreo',[familiaresController::class,'recuperarCuentaPCorreo']);
Route::post('Familiares/VerificarCodigoRecuperacion',[familiaresController::class,'verificarCodigoRecuperacion']);
Route::post('Familiares/RestablecerContrasena',[familiaresController::class,'restablecerContrasena']);
Route::get('Familiares/getInfo',[familiaresController::class,'getInfo']);