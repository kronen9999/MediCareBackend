<?php

namespace App\Http\Controllers;

use App\Jobs\cuidadoresEnviCodigoRecuperacion;
use App\Jobs\cuidadoresNotificarFamiliarRecuperacion;
use Illuminate\Http\Request;

use App\Models\cuidadores as cu;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
    
    
}
