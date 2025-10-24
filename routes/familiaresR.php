<?php

use App\Http\Controllers\familiaresController;
use Illuminate\Support\Facades\Route;

Route::post('Familiares/Registro',[familiaresController::class,'registro']);
Route::get('Familiares/Verificacion/{CorreoE}/{CodigoVerificacion}',[familiaresController::class,'verificacion']);
Route::post('Familiares/Login',[familiaresController::class,'login']);
Route::post('Familiares/RecupearCuentaPCorreo',[familiaresController::class,'recuperarCuentaPCorreo']);
Route::post('Familiares/VerificarCodigoRecuperacion',[familiaresController::class,'verificarCodigoRecuperacion']);
Route::post('Familiares/RestablecerContrasena',[familiaresController::class,'restablecerContrasena']);
Route::post('Familiares/ObtenerAtributosGenerales',[familiaresController::class,'obtenerAtributosGenerales']);

//rutas del familiar del apartado de perfil
Route::post('Familiares/Perfil/ObtenerPerfil',[familiaresController::class,'ObtenerPerfil']);
Route::post('Familiares/Perfil/ActualizarInformacionPersonal',[familiaresController::class,'ActualizarInformacionPersonal']);
Route::post('Familiares/Perfil/ActualizarInformacionCuenta',[familiaresController::class,'ActualizarInformacionCuenta']);
Route::post('Familiares/Perfil/ActualizarContrasena',[familiaresController::class,'actualizarContrasena']);

//rutas de familiar para administrar cuidadores
Route::post('Familiares/Cuidadores/AgregarCuidador',[familiaresController::class,'agregarCuidador']);
Route::post('Familiares/Cuidadores/EditarCuidadorInformacionPerfil',[familiaresController::class,'editarCuidadorInformacionPerfil']);
Route::post('Familiares/Cuidadores/ObtenerCuidadores',[familiaresController::class,'obtenerCuidadores']);
Route::post('Familiares/Cuidadores/ObtenerCuidador',[familiaresController::class,'obtenerCuidador']);
Route::get('Familiares/getInfo',[familiaresController::class,'getInfo']);
Route::post('Familiares/Cuidadores/EditarCuidadorInformacionCuenta',[familiaresController::class,'editarCuidadorInformacionCuenta']);
Route::post('Familiares/Cuidadores/CambiarContrasenaCuidador',[familiaresController::class,'cambiarContrasenaCuidador']);
Route::post('Familiares/Cuidadores/EliminarCuidador',[familiaresController::class,'eliminarCuidador']);
Route::post('Familiares/Cuidadores/ObtenerCuidadoresNoAsignados',[familiaresController::class,'obtenerCuidadoresNoAsignados']);

//rutas del familiar para administrar pacientes
Route::post('Familiares/Pacientes/AgregarPaciente',[familiaresController::class,'agregarPaciente']);
Route::post('Familiares/Pacientes/ObtenerPacientes',[familiaresController::class,'obtenerPacientes']);
Route::post('Familiares/Pacientes/ObtenerPaciente',[familiaresController::class,'obtenerPaciente']);
Route::post('Familiares/Pacientes/EditarPacienteInformacionPerfil',[familiaresController::class,'editarPaciente']);
Route::post('Familiares/Pacientes/AsignarCuidadorAPaciente',[familiaresController::class,'asignarCuidadorPaciente']);
Route::post('Familiares/Pacientes/DesasignarCuidadorAPaciente',[familiaresController::class,'desasignarCuidador']);
Route::post('Familiares/Pacientes/EliminarPaciente',[familiaresController::class,'eliminarPaciente']);
//Rutas para administrar los medicamentos de los famliares
Route::post('Familiares/Pacientes/Medicamentos/ObtenerMedicamentos',[familiaresController::class,'obtenerMedicamentos']);
Route::post('Familiares/Pacientes/Medicamentos/ObtenerMedicamento',[familiaresController::class,'obtenerMedicamento']);
Route::post('Familiares/Pacientes/Medicamentos/AgregarMedicamento',[familiaresController::class,'agregarMedicamento']);
