<?php

use Illuminate\Support\Facades\Route;

Route::post('Cuidadores/login', [App\Http\Controllers\cuidadoresController::class, 'login']);
