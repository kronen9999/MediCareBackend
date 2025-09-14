<?php

namespace App\Http\Controllers;

use App\Models\familiares as fam;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Mail\familiaresEnvioCodigoMail as fmEnvioC;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class familiaresController extends Controller
{
    //metodo para obtener la informacionGeneral
    public function getInfo(Request $request){
        $request->validate([
            'IdPaciente'=>'required',
            'TokenAcceso'=>'required'
        ]);

        $familiar=fam::where('IdPaciente',$request->IdPaciente)->get();

        if (!$familiar)
        {
            return response()->json(['message' => 'No encontrado'], 404);
        } else {
            return response()->json($familiar, 200);
        }
    }

    //metodo para insertar un familiar 
    public function registro(Request $request)
    {
        try {
            $request->validate([
                'CorreoE' => 'required|email',
                'Contrasena' => 'required|min:8|max:20',
            ]);
            $familiar = new fam();
            $usuarioRegistrado=$familiar->where('CorreoE',$request->CorreoE)->first();
            if ($usuarioRegistrado)
            {
                return response()->json(["message"=>"El correo ya está registrado"],409);
            }
            
            DB::beginTransaction();
           
            $familiar->CorreoE = $request->CorreoE;
            $familiar->Contrasena = $request->Contrasena;
            $codigoVerificacion = Str::random(10);
            $familiar->CodigoVerificacion = $codigoVerificacion;
            $tokenAcceso = Str::random(50);
            $familiar->TokenAcceso = $tokenAcceso;
            $familiar->save();

            Mail::to($request->CorreoE)->send(new fmEnvioC($request->CorreoE, $codigoVerificacion));
            DB::commit();

            return response()->json(['message' => 'Familiar registrado'], 201);  
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    //metodo para verificar el codigo enviado al correo
    public function verificacion($CorreoE=null,$codigoVerificacion=null)
    {

        if (!$codigoVerificacion || !$CorreoE)
        {
            return response()->json(['message' => 'Faltan parametros'], 400);
        }

        $correo=$CorreoE;
        $codigo=$codigoVerificacion;

        $objFamiliar=new fam();

     $Usuario=$objFamiliar->where('CorreoE', $correo)->first();


     if (!$Usuario)
     {
         return response()->json(['message' => 'Usuario No encontrado'], 404);
     }

    if ($Usuario->UsuarioVerificado=="1")
        {
            return response()->json(['message' => 'Usuario ya verificado'], 409);
        }

        if ($Usuario->CodigoVerificacion !== $codigo)
        {
            return response()->json(['message' => 'Codigo de verificacion incorrecto'], 401);
        }

        $Usuario->UsuarioVerificado="1";
        $Usuario->save();

        return response()->json(['message' => 'Usuario verificado'], 200);
    
    }
    

}
