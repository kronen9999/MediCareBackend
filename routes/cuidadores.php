<?php

use App\Http\Controllers\cuidadoresController;
use Illuminate\Support\Facades\Route;

Route::post('Cuidadores/login', [App\Http\Controllers\cuidadoresController::class, 'login']);
Route::post('Cuidadores/RecupearCuentaPCorreo', [App\Http\Controllers\cuidadoresController::class, 'recuperarCuentaPCorreo']);
Route::post('Cuidadores/VerificarCodigoRecuperacion', [App\Http\Controllers\cuidadoresController::class, 'verificarCodigoRecuperacion']);
Route::post('Cuidadores/RestablecerContrasena', [App\Http\Controllers\cuidadoresController::class, 'restablecerContrasena']);
Route::post('Cuidadores/AlertaFamiliar',[cuidadoresController::class,'alertaRecuperacionFamiliar']);
//Metodos para administrar el perfil del cuidador
Route::post('Cuidadores/Perfil/ObtenerPerfilCompleto', [cuidadoresController::class, 'ObtenerPerfilCompleto']);
Route::post('Cuidadores/Perfil/ObtenerPerfilBasico', [cuidadoresController::class, 'obtenerPerfilBasico']);
Route::put('Cuidadores/Perfil/ActualizarInformacionPersonal',[cuidadoresController::class,'actualizarInformacionPersonal']);
Route::put('Cuidadores/Perfil/ActualizarInformacionCuenta', [cuidadoresController::class, 'actualizarInformacionCuenta']);
Route::put('Cuidadores/Perfil/ActualizarContrasena', [cuidadoresController::class, 'actualizarContrasena']);
//Metodos de administracion de pacientes
Route::post('Cuidadores/Pacientes/saberPacienteAsignado',[cuidadoresController::class,'saberPacienteAsignado']);
Route::post('Cuidadores/Pacientes/ObtenerProximosRecordatorios',[cuidadoresController::class,'obtenerProximosRecordatorios']);
Route::post('Cuidadores/Pacientes/AdministrarMedicamento',[cuidadoresController::class,'administrarMedicamento']);
Route::post('Cuidadores/Pacientes/ObtenerMetricasRecordatorios', [cuidadoresController::class, 'obtenerMetricasRecordatorios']);
Route::post('Cuidadores/Pacientes/ObtenerHistorialRecordatorios', [cuidadoresController::class, 'obtenerHistorialReccordatorios']);
