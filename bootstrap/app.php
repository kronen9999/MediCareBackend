<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens([
            'Familiares/Registro',
           'Familiares/Login',
           'Familiares/RecupearCuentaPCorreo',
           'Familiares/VerificarCodigoRecuperacion',
           'Familiares/RestablecerContrasena',
           'Familiares/Cuidadores/AgregarCuidador',
           'Cuidadores/login',
           'Cuidadores/RecupearCuentaPCorreo',
           'Cuidadores/VerificarCodigoRecuperacion',
           'Cuidadores/RestablecerContrasena',
           'Cuidadores/AlertaFamiliar',
           'Familiares/Perfil/ActualizarInformacionPersonal',
           'Familiares/Perfil/ActualizarInformacionCuenta',
           'Familiares/Perfil/ActualizarContrasena',
           'Familiares/Cuidadores/EditarCuidadorInformacionPerfil',
           
        ]);
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
