<?php

namespace App\Http\Controllers;

use App\Jobs\cuidadoresEnviCodigoRecuperacion;
use App\Jobs\cuidadoresNotificarFamiliarRecuperacion;
use Illuminate\Http\Request;

use App\Models\cuidadores as cu;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

use function PHPUnit\Framework\isEmpty;

class cuidadoresController extends Controller
{

    //metodo para el login del cuidador
 public function login(Request $request)
    {
      
      $credencial=$request->Credencial;
      $Contrasena=$request->Contrasena;
         
      if (!$credencial && !$Contrasena)
        {
            return response()->json(['message' => 'Faltan parametros'], 400);
        }

        if (!$credencial || !$Contrasena)
        {
            return response()->json(['message' => 'Faltan parametros'], 400);
        }

        $Usuario=cu::where(function($query) use ($credencial){
            $query->where('CorreoE',$credencial)
                  ->orWhere('Usuario',$credencial);
        })->first();
        if (!$Usuario)
        {
            return response()->json(['message' => 'Usuario No encontrado'], 404);
        }
        if (!Hash::check($Contrasena, $Usuario->Contrasena))
        {
            return response()->json(['message' => 'Contraseña incorrecta'], 401);
        }
        
        $Usuario->TokenAcceso=Str::random(50);
        $Usuario->save();
        return response()->json(['message' => 'Login exitoso',
    'Usuario'=>[
        "IdUsuario"=>$Usuario->IdCuidador,
        "TokenAcceso"=>$Usuario->TokenAcceso,
    ]], 200);

    }

    //metodo para enviar el codigo de verificacion por correo al cuidador
   
     public function recuperarCuentaPCorreo(Request $request)
    {
        try {
        $request->validate([
            'CorreoE' => 'required|email',
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        $firstError = collect($e->errors())->flatten()->first();
        return response()->json(['error' => $firstError], 422);
    } 

    try{
        DB::beginTransaction();
$correo=$request->CorreoE;

        $Usuario=cu::where('CorreoE',$correo)->first();
        if (!$Usuario)
        {
            return response()->json(['message' => 'Usuario No encontrado'], 404);
        }

        $codigoVerificacion = Str::random(10);
        $Usuario->CodigoVerificacion = $codigoVerificacion;
        $Usuario->save();
        cuidadoresEnviCodigoRecuperacion::dispatch($correo, $codigoVerificacion);
        DB::commit();
        return response()->json(['message' => 'Correo de recuperacion enviado'], 200);
    }catch(\Exception $e){
        DB::rollBack();
        return response()->json(['message' => $e->getMessage()], 500);
    }
        
    }

    //metodo para verificar que el codigo de recuperacion sea el correcto
    public function verificarCodigoRecuperacion(Request $request)
    {

        try{
        $request->validate([
            'CorreoE' => 'required|email',
            'CodigoVerificacion' => 'required',
        ]);
        }catch(\Illuminate\Validation\ValidationException $e){
            $firstError = collect($e->errors())->flatten()->first();
            return response()->json(['error' => $firstError], 422);
        }

        $correo=$request->CorreoE;
        $codigo=$request->CodigoVerificacion;

        $Usuario=cu::where('CorreoE', $correo)->first();
        if (!$Usuario)
        {
            return response()->json(['message' => 'Usuario No encontrado'], 404);
        }

        if ($Usuario->CodigoVerificacion != $codigo)
        {
            return response()->json(['message' => 'Codigo de verificacion incorrecto'], 401);
        }
        else
        {
            return response()->json(['message' => 'Codigo de verificacion correcto'], 200);
        }
            
    }

    //metodo para restablecer la contrasena
    public function restablecerContrasena(Request $request)
    {
        try{

            $request->validate([
                'CorreoE' => 'required|email',
                'CodigoVerificacion' => 'required',
                'NuevaContrasena' => 'required|min:8|max:20',
            ]);

        }catch(\Illuminate\Validation\ValidationException $e){
           $firstError = collect($e->errors())->flatten()->first();
              return response()->json(['error' => $firstError], 422);
        }


        $correo=$request->CorreoE;
        $codigo=$request->CodigoVerificacion;
        $nuevaContrasena=$request->NuevaContrasena;

        $Usuario=cu::where('CorreoE', $correo)->first();
        if (!$Usuario)
        {
            return response()->json(['message' => 'Usuario No encontrado'], 404);
        }
        if ($Usuario->CodigoVerificacion != $codigo)
        {
            return response()->json(['message' => 'Codigo de verificacion incorrecto'], 401);
        }
        $Usuario->Contrasena = $nuevaContrasena;
        $Usuario->save();
        return response()->json(['message' => 'Contrasena restablecida'], 200);
    }
    
    public function alertaRecuperacionFamiliar(Request $request)
    {
        try{
            $request->validate([
                'Usuario' => 'required',
            ]);
        }catch(\Illuminate\Validation\ValidationException $e){
            $firstError = collect($e->errors())->flatten()->first();
              return response()->json(['error' => $firstError], 422);
        }

        $Usuario=cu::where('Usuario', $request->Usuario)->first();
        if (!$Usuario)
        {
            return response()->json(['message' => 'Usuario No encontrado'], 404);
        }

        $UsernameRecuperar=$Usuario->Usuario;
        $CorreoFamiliar=$Usuario->familiar->CorreoE;
        $fechaHoraActual = now()->toDateTimeString();

        cuidadoresNotificarFamiliarRecuperacion::dispatch($UsernameRecuperar, $CorreoFamiliar, $fechaHoraActual);

        return response()->json(['message' => 'Notificacion enviada al familiar'], 200);
        

    }

    /////////////////Metodos del perfil del cuidador//////////////////////////////////////////////////

    public function ObtenerPerfilCompleto(Request $request)
    {
        try{
            $request->validate([
                'IdCuidador' => 'required',
                'TokenAcceso' => 'required',
            ]);
        }catch(\Illuminate\Validation\ValidationException $e){
            $firstError = collect($e->errors())->flatten()->first();
              return response()->json(['error' => $firstError], 422);
        }
        
        $Usuario=cu::where("IdCuidador",$request->IdCuidador)->first();
        if (!$Usuario)
        {
            return response()->json(['message' => 'Usuario No encontrado'], 404);
        }
        if ($Usuario->TokenAcceso != $request->TokenAcceso)
        {
            return response()->json(['message' => 'Token de acceso invalido'], 401);
        }

        $informacionContacto=$Usuario->informacionContactoCuidador()->first();
        $familiar=$Usuario->familiar()->first();
        $informacionContactoFamiliar=$familiar->informacionContactoFamiliar()->first();
        $perfilCompleto=[
            'IdCuidador'=>$Usuario->IdCuidador,
            'Nombre'=>$Usuario->Nombre,
            'ApellidoPaterno'=>$Usuario->ApellidoP,
            'ApellidoMaterno'=>$Usuario->ApellidoM,
            'Usuario'=>$Usuario->Usuario,
            'CorreoE'=>$Usuario->CorreoE,
            'Telefono1'=>$informacionContacto ? $informacionContacto->Telefono1 : null,
            'Telefono2'=>$informacionContacto ? $informacionContacto->Telefono2 : null,
            'Direccion'=>$informacionContacto ? $informacionContacto->Direccion : null,
            'NombreFamiliar'=>$familiar->Nombre,
            'ApellidoPFamiliar'=>$familiar->ApellidoP,
            'ApellidoMFamiliar'=>$familiar->ApellidoM,
            'CorreoEFamiliar'=>$familiar->CorreoE,
            'DireccionFamiliar'=>$informacionContactoFamiliar->Direccion,
            'Telefono1Familiar'=>$informacionContactoFamiliar->Telefono1,
            'Telefono2Familiar'=>$informacionContactoFamiliar->Telefono2,

        ];
        return response()->json($perfilCompleto, 200);
    
}

    public function obtenerPerfilBasico(Request $request)
    {
         try{
            $request->validate([
                'IdCuidador' => 'required',
                'TokenAcceso' => 'required',
            ]);
        }catch(\Illuminate\Validation\ValidationException $e){
            $firstError = collect($e->errors())->flatten()->first();
              return response()->json(['error' => $firstError], 422);
        }

         $Usuario=cu::where("IdCuidador",$request->IdCuidador)->first();

        if (!$Usuario)
        {
            return response()->json(['message' => 'Usuario No encontrado'], 404);
        }
        if ($Usuario->TokenAcceso != $request->TokenAcceso)
        {
            return response()->json(['message' => 'Token de acceso invalido'], 401);
        }
        $informacionUsuario=$Usuario->informacionContactoCuidador()->first();

        return response()->json([
            'IdCuidador'=>$Usuario->IdCuidador,
            'Nombre'=>$Usuario->Nombre,
            'ApellidoPaterno'=>$Usuario->ApellidoP,
            'ApellidoMaterno'=>$Usuario->ApellidoM,
            'Usuario'=>$Usuario->Usuario,
            'CorreoE'=>$Usuario->CorreoE,
            'Telefono1'=>$informacionUsuario ? $informacionUsuario->Telefono1 : null,
            'Telefono2'=>$informacionUsuario ? $informacionUsuario->Telefono2 : null,
            'Direccion'=>$informacionUsuario ? $informacionUsuario->Direccion : null,
        ], 200);

    }

    public function actualizarInformacionPersonal (Request $request)
    {
         try{
            $request->validate([
                'IdCuidador' => 'required',
                'TokenAcceso' => 'required',
                'Nombre'=>["required","string","max:100"],
                'ApellidoP'=>["required","string","max:100"],
                'ApellidoM'=>["nullable","string","max:100"],
                'Direccion'=>["nullable","string","max:250"],
                "Telefono1"=>["nullable","numeric","digits:10"],
                "Telefono2"=>["nullable","numeric","digits:10"],
            ]);
        }catch(\Illuminate\Validation\ValidationException $e){
            $firstError = collect($e->errors())->flatten()->first();
              return response()->json(['error' => $firstError], 422);
        }

         $Usuario=cu::where("IdCuidador",$request->IdCuidador)->first();

        if (!$Usuario)
        {
            return response()->json(['message' => 'Usuario No encontrado'], 404);
        }
        if ($Usuario->TokenAcceso != $request->TokenAcceso)
        {
            return response()->json(['message' => 'Token de acceso invalido'], 401);
        }
        $informacionUsuario=$Usuario->informacionContactoCuidador()->first();
        try{
     DB::beginTransaction();

     $Usuario->Nombre=$request->Nombre;
    $Usuario->ApellidoP = $request->ApellidoP;
    $Usuario->ApellidoM = $request->ApellidoM;
    $Usuario->save();

    if ($informacionUsuario) {
        $informacionUsuario->Direccion = $request->Direccion;
        $informacionUsuario->Telefono1 = $request->Telefono1;
        $informacionUsuario->Telefono2 = $request->Telefono2;
        $informacionUsuario->save();
    }
      DB::commit();
    return response()->json(['message' => 'Información actualizada correctamente'], 200);

     
        }catch(Exception $e)
        {
        DB::rollBack();
        return response()->json(["message"=>$e->getMessage()]);
        }
    }

    public function actualizarInformacionCuenta(Request $request)
    {
        try{
            $request->validate([
                'IdCuidador' => 'required',
                'TokenAcceso' => 'required',
                'Usuario'=>["required","string","max:50"],
                'CorreoE'=>["nullable","string","max:250","email"],
            ]);
        }catch(\Illuminate\Validation\ValidationException $e){
            $firstError = collect($e->errors())->flatten()->first();
              return response()->json(['error' => $firstError], 422);
        }

         $Usuario=cu::where("IdCuidador",$request->IdCuidador)->first();

        if (!$Usuario)
        {
            return response()->json(['message' => 'Usuario No encontrado'], 404);
        }
        if ($Usuario->TokenAcceso != $request->TokenAcceso)
        {
            return response()->json(['message' => 'Token de acceso invalido'], 401);
        }

        try{
            DB::beginTransaction();

            $correo="";
            $usuario="";

            if (empty($request->Usuario))
            {
                return response()->json(["error"=>"El campo del usuario es obligatorio"],422);
            }
            else{
                if ($request->Usuario!=$Usuario->Usuario)
                {
                    if (cu::where("Usuario",$request->Usuario)->exists())
                        {
                          return response()->json(["error"=>"Nombre de usuario ocupado"],422);
                        }
                }
                
            $usuario=$request->Usuario;
            }
            

            

            if (empty($request->CorreoE))
            {
                $correo=null;
            }
            else 
            {
                if (strtolower($request->CorreoE) != $Usuario->CorreoE)
                {
               if (cu::where("CorreoE",$request->CorreoE)->exists())
                  {
                  return response()->json(["error"=>"Correo electronico ocupado"],422);
                  }  
                }
            $correo=$request->CorreoE; 
            }
            
            $Usuario->Usuario=$usuario;
            $Usuario->CorreoE=$correo;

            $Usuario->save();

            DB::commit();

            return response()->json(["message"=>"Datos actualizados correctamente"],200);
        }catch(Exception $e)
        {
            DB::rollBack();
            return response()->json(["message"=>$e->getMessage()],500);
        }

    }

    public function actualizarContrasena (Request $request)
    {
        
        try{
          $request->validate([
            "IdCuidador"=>'required',
            "TokenAcceso"=>'required',
            "ContrasenaActual"=>["required","min:8","max:20"],
            "NuevaContrasena"=>["required","min:8","max:20"],
          ]);
        }catch(\Illuminate\Validation\ValidationException $e){
           $firstError = collect($e->errors())->flatten()->first();
              return response()->json(['error' => $firstError], 422);
        }

         $Usuario=cu::where("IdCuidador",$request->IdCuidador)->first();

        if (!$Usuario)
        {
            return response()->json(['message' => 'Usuario No encontrado'], 404);
        }
        if ($Usuario->TokenAcceso != $request->TokenAcceso)
        {
            return response()->json(['message' => 'Token de acceso invalido'], 401);
        }
         if (!Hash::check($request->ContrasenaActual, $Usuario->Contrasena))
        {
            return response()->json(['message' => 'Contraseña actual incorrecta'], 401);
        }

         try{
            DB::beginTransaction();

            $Usuario->Contrasena = $request->NuevaContrasena;
            $Usuario->save();
            DB::commit();

            return response()->json(['message'=>'Contraseña actualizada'],200);

        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }

    }

    public function saberPacienteAsignado(Request $request)
    {
     try{
          $request->validate([
            "IdCuidador"=>'required',
            "TokenAcceso"=>'required',
          ]);
        }catch(\Illuminate\Validation\ValidationException $e){
           $firstError = collect($e->errors())->flatten()->first();
              return response()->json(['error' => $firstError], 422);
        }
           $Usuario=cu::where("IdCuidador",$request->IdCuidador)->first();

        if (!$Usuario)
        {
            return response()->json(['message' => 'Usuario No encontrado'], 404);
        }
        if ($Usuario->TokenAcceso != $request->TokenAcceso)
        {
            return response()->json(['message' => 'Token de acceso invalido'], 401);
        }
        $paciente=$Usuario->pacientes()->first();
        $informacionContactoPaciente=$paciente ? $paciente->informacionContactoPaciente()->first() : null;
        if (!$paciente)
        {
            return response()->json(['message' => 'No Asignado'], 200);
        }
        return response()->json(["message"=>"Asignado",
    "Nombre"=>$paciente->Nombre,
    "ApellidoP"=>$paciente->ApellidoP,
    "ApellidoM"=>$paciente->ApellidoP,
    "Direccion"=>$informacionContactoPaciente->Direccion,
    "Telefono1"=>$informacionContactoPaciente->Telefono1,
    "Telefono2"=>$informacionContactoPaciente->Telefono2], 200);
    }

    public function obtenerProximosRecordatorios(Request $request)
    {
        try{
          $request->validate([
            "IdCuidador"=>'required',
            "TokenAcceso"=>'required',
          ]);
        }catch(\Illuminate\Validation\ValidationException $e){
           $firstError = collect($e->errors())->flatten()->first();
              return response()->json(['error' => $firstError], 422);
        }
           $Usuario=cu::where("IdCuidador",$request->IdCuidador)->first();

        if (!$Usuario)
        {
            return response()->json(['message' => 'Usuario No encontrado'], 404);
        }
        if ($Usuario->TokenAcceso != $request->TokenAcceso)
        {
            return response()->json(['message' => 'Token de acceso invalido'], 401);
        }

        $paciente=$Usuario->pacientes()->first();
        if (!$paciente)
        {
            return response()->json(['message' => 'No Asignado'], 400);
        }

        $recordatoriosProximos=$Usuario->historialAdministracion()->where('IdCuidador','=',$Usuario->IdCuidador)->where("Estado","=","No Administrado")->get();

        $listaRecordatorios=[];

        foreach ($recordatoriosProximos as $recordatorio)
        {
         $listaRecordatorios[]=[
        'idHistorial' => $recordatorio->idHistorial,
        'FechaProgramada' => $recordatorio->FechaProgramada,
        'NombreM' => $recordatorio->NombreM,
        'NombreP' => $recordatorio->NombreP,
        'Dosis' => $recordatorio->Dosis,
        'UnidadDosis' => $recordatorio->UnidadDosis,
        'Notas' => $recordatorio->Notas,
         ];
        }
        

        return response()->json(['recordatorios'=>$listaRecordatorios], 200);
    }

    public function administrarMedicamento(Request $request)
    {
 try{
          $request->validate([
            "IdCuidador"=>'required',
            "TokenAcceso"=>'required',
            "IdHistorial"=>'required',
            "FechaAdministracion"=>'required|date_format:Y-m-d H:i:s',
          ]);
        }catch(\Illuminate\Validation\ValidationException $e){
           $firstError = collect($e->errors())->flatten()->first();
              return response()->json(['error' => $firstError], 422);
        }
           $Usuario=cu::where("IdCuidador",$request->IdCuidador)->first();

        if (!$Usuario)
        {
            return response()->json(['message' => 'Usuario No encontrado'], 404);
        }
        if ($Usuario->TokenAcceso != $request->TokenAcceso)
        {
            return response()->json(['message' => 'Token de acceso invalido'], 401);
        }

        $paciente=$Usuario->pacientes()->first();
        if (!$paciente)
        {
            return response()->json(['message' => 'No Asignado'], 400);
        }
        $historialMedicamento=$Usuario->historialAdministracion()->where("idHistorial","=",$request->IdHistorial)->first();
    if (!$historialMedicamento)
    {
      return response()->json(["message"=>"Registro no encontrado"],404);
    }
        try{
        DB::beginTransaction();

        $horaAdministracionParseada=carbon::createFromFormat("Y-m-d H:i:s",$request->FechaAdministracion);
        $horaProgramadaParseada=carbon::createFromFormat('Y-m-d H:i:s',$historialMedicamento->FechaProgramada);
        if ($horaAdministracionParseada<$horaProgramadaParseada)
        {
            return response()->json(["error"=>"No puede dar el medicamento antes de la fecha programada"],422);
        }

        if ($historialMedicamento->IdHorario==null)
        {
        $historialMedicamento->HoraAdministracion=$request->FechaAdministracion;
        $historialMedicamento->Estado="Administrado";
        $historialMedicamento->Administro=$Usuario->Nombre?$Usuario->Nombre:"Cuidador";
        $historialMedicamento->save();
        DB::commit();
        return response()->json(["message"=>"Administracion registrada dado a que el medicamento se elimino no se generara el siguiente recordatorio de este medicamento",
    "FechaSiguienteDosis"=>null,"NombreM"=>null,"NombreP"=>null],200);
        }else{

            $horarioMedicamento=$historialMedicamento->Horario()->first();
            $medicamento=$horarioMedicamento->medicamento()->first();
            $paciente=$medicamento->paciente()->first();

            if($medicamento->MedicamentoActivo==0)
            {
                $historialMedicamento->HoraAdministracion=$request->FechaAdministracion;
                $historialMedicamento->Estado="Administrado";
                $historialMedicamento->Administro=$Usuario->Nombre?$Usuario->Nombre:"Cuidador";
                $historialMedicamento->save();
                DB::commit();
                return response()->json(["message"=>"Administracion registrada dado a que el medicamento esta inactivo no se generara el siguiente recordatorio de este medicamento",
            "FechaSiguienteDosis"=>null,"NombreM"=>null,"NombreP"=>null],200);
            }
            
            if ($medicamento->MedicamentoActivo==1)
            {
                $historialMedicamento->HoraAdministracion=$request->FechaAdministracion;
                $historialMedicamento->Estado="Administrado";
                $historialMedicamento->Administro=$Usuario->Nombre?$Usuario->Nombre:"Cuidador";
                $historialMedicamento->save();

                $nuevaFechaProgramada=$horaAdministracionParseada->copy()->addHours((int)$horarioMedicamento->IntervaloHoras)->addMinutes((int)$horarioMedicamento->IntervaloMinutos);

                $siguienteDosis=$nuevaFechaProgramada->format('Y-m-d H:i:s');
                  $nuevoRegistro= $horarioMedicamento->historialAdministracion()->create([
                'FechaProgramada'=>$siguienteDosis,
                'HoraAdministracion'=>null,
                'Estado'=>'No Administrado',
                'Administro'=>null,
                'IdFamiliar'=>$historialMedicamento->IdFamiliar,
                'IdCuidador'=>$Usuario->IdCuidador,
                'NombreM'=>$medicamento->NombreM,
                'NombreP'=>$paciente->Nombre,
                'Dosis'=>$horarioMedicamento->Dosis,
                'UnidadDosis'=>$horarioMedicamento->UnidaDosis,
                'Notas'=>$horarioMedicamento->Notas,
            ]);
            DB::commit();

            return response()->json(["message"=>"Administracion del medicamento  realizada,se ha generado el siguiente recordatorio de la dosis",
        "FechaSiguienteDosis"=>$nuevoRegistro->FechaProgramada,"NombreM"=>$nuevoRegistro->NombreM,"NombreP"=>$nuevoRegistro->NombreP],200);
               
           }
        }

        return response()->json(["message"=>"Medicamento administrado correctamente"],200);
        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function obtenerMetricasRecordatorios(Request $request)
    {
      try{
          $request->validate([
            "IdCuidador"=>'required',
            "TokenAcceso"=>'required',
          ]);
        }catch(\Illuminate\Validation\ValidationException $e){
           $firstError = collect($e->errors())->flatten()->first();
              return response()->json(['error' => $firstError], 422);
        }
           $Usuario=cu::where("IdCuidador",$request->IdCuidador)->first();

        if (!$Usuario)
        {
            return response()->json(['message' => 'Usuario No encontrado'], 404);
        }
        if ($Usuario->TokenAcceso != $request->TokenAcceso)
        {
            return response()->json(['message' => 'Token de acceso invalido'], 401);
        }


        
    $recordatorios=$Usuario->historialAdministracion()->get();

    $recordatoriosCancelados=$recordatorios->where("Estado","=","Cancelado")->count();
    $recordatoriosAdministrados=$recordatorios->where("Estado","=","Administrado")->count();

    return response()->json([
        "RecordatoriosCancelados"=>$recordatoriosCancelados,
        "RecordatoriosAdministrados"=>$recordatoriosAdministrados
    ],200);
  
    }

    public function obtenerHistorialReccordatorios(Request $request){
    try{
        $request->validate([
            'IdCuidador'=>['Required'],
            'TokenAcceso'=>['Required'],
            'FechaDatos'=>['Required','date_format:Y-m-d']
        ]);
    }catch(\Illuminate\Validation\ValidationException $e)
    {
    $firstError = collect($e->errors())->flatten()->first();
    return response()->json(['error' => $firstError], 422);
    }

        $Usuario=cu::where("IdCuidador",$request->IdCuidador)->first();

        if (!$Usuario)
        {
            return response()->json(['message' => 'Usuario No encontrado'], 404);
        }
        if ($Usuario->TokenAcceso != $request->TokenAcceso)
        {
            return response()->json(['message' => 'Token de acceso invalido'], 401);
        }

        $recordatorios=$Usuario->historialAdministracion()->whereDate('FechaProgramada','=',$request->FechaDatos)->where("Estado","!=","No Administrado")->orderBy("FechaProgramada","asc")->get();
        $listaRecordatorios=[];

        foreach($recordatorios as $recordatorio)
        {
                $listaRecordatorios[]=[
                    "IdHistorial"=>$recordatorio->idHistorial,
                    "FechaProgramada"=>$recordatorio->FechaProgramada,
                    "HoraAdministracion"=>$recordatorio->HoraAdministracion,
                    "NombreM"=>$recordatorio->NombreM,
                    "NombreP"=>$recordatorio->NombreP,
                    "Dosis"=>$recordatorio->Dosis,
                    "UnidadDosis"=>$recordatorio->UnidadDosis,
                    "Notas"=>$recordatorio->Notas,
                    "Administro"=>$recordatorio->Administro,
                    "Estado"=>$recordatorio->Estado,
                    "NombreCuidador"=>$Usuario->Nombre?$Usuario->Nombre:null
                ];
        }

        return response()->json(["Recordatorios"=>$listaRecordatorios],200);



    }
}