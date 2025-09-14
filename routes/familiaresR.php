<?php

use App\Http\Controllers\familiaresController;
use Illuminate\Support\Facades\Route;

Route::post('Familiares/Registro',[familiaresController::class,'registro']);
Route::get('Familiares/Verificacion/{CorreoE}/{CodigoVerificacion}',[familiaresController::class,'verificacion']);
Route::get('Familiares/getInfo',[familiaresController::class,'getInfo']);