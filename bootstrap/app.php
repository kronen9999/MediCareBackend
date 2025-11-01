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
           'Familiares/Cuidadores/EditarCuidadorInformacionCuenta',
           'Familiares/Cuidadores/CambiarContrasenaCuidador',
           'Familiares/Pacientes/AgregarPaciente',
           'Familiares/Pacientes/EditarPacienteInformacionPerfil',
           'Familiares/Pacientes/AsignarCuidadorAPaciente',
           'Familiares/Pacientes/DesasignarCuidadorAPaciente',
           'Familiares/Pacientes/EliminarPaciente',
           'Familiares/Cuidadores/EliminarCuidador',
           'Familiares/Perfil/ObtenerPerfil',
           'Familiares/ObtenerAtributosGenerales',
           'Familiares/Cuidadores/ObtenerCuidador',
           'Familiares/Cuidadores/ObtenerCuidadores',
           'Familiares/Pacientes/ObtenerPacientes',
           'Familiares/Pacientes/ObtenerPaciente',
           'Familiares/Cuidadores/ObtenerCuidadoresNoAsignados',
           'Cuidadores/Perfil/ObtenerPerfilCompleto',
           'Cuidadores/Perfil/ObtenerPerfilBasico',
           'Cuidadores/Perfil/ActualizarInformacionPersonal',
           'Cuidadores/Perfil/ActualizarInformacionCuenta',
           'Cuidadores/Perfil/ActualizarContrasena',
           'Familiares/Pacientes/Medicamentos/AgregarMedicamentoHorario',
           'Familiares/Pacientes/Medicamentos/ObtenerMedicamentos',
           'Familiares/Pacientes/Medicamentos/ObtenerMedicamento',
           'Familiares/Pacientes/Medicamentos/EditarMedicamento',
           'Familiares/Pacientes/Medicamentos/EditarHorarioMedicamento',
           'HistorialAdministracion/ObtenerProximosRecordatorios',
           'HistorialAdministracion/administrarMedicamentos',
           'HistorialAdministracion/CancelarAdministracionMedicamento',
           'HistorialAdministracion/ObtenerHistorialAdministracion',
           'Familiares/Pacientes/Medicamentos/HabilitarMedicamento',
           'Familiares/Pacientes/Medicamentos/DesabilitarMedicamento',
        ]);
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
