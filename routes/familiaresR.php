<?php

use App\Http\Controllers\familiaresController;
use Illuminate\Support\Facades\Route;

Route::post('Familiares/Registro',[familiaresController::class,'registro']);
Route::get('Familiares/Verificacion/{CorreoE}/{CodigoVerificacion}',[familiaresController::class,'verificacion']);
Route::post('Familiares/Login',[familiaresController::class,'login']);
Route::post('Familiares/RecupearCuentaPCorreo',[familiaresController::class,'recuperarCuentaPCorreo']);
Route::post('Familiares/VerificarCodigoRecuperacion',[familiaresController::class,'verificarCodigoRecuperacion']);
Route::post('Familiares/RestablecerContrasena',[familiaresController::class,'restablecerContrasena']);
Route::post('Familiares/AgregarCuidador',[familiaresController::class,'agregarCuidador']);
//rutas del familiar del apartado de perfil
Route::get('Familiares/Perfil/ObtenerPerfil',[familiaresController::class,'ObtenerPerfil']);
Route::post('Familiares/Perfil/ActualizarInformacionPersonal',[familiaresController::class,'ActualizarInformacionPersonal']);
Route::post('Familiares/Perfil/ActualizarInformacionCuenta',[familiaresController::class,'ActualizarInformacionCuenta']);
Route::post('Familiares/Perfil/ActualizarContrasena',[familiaresController::class,'actualizarContrasena']);
Route::get('Familiares/getInfo',[familiaresController::class,'getInfo']);