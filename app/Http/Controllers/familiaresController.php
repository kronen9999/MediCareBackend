<?php

namespace App\Http\Controllers;

use App\Jobs\familiaresEnvioCodigoRecuperacion;
use App\Models\cuidadores;
use App\Models\familiares as fam;

use App\Models\informacionContactoCuidador;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Mail\familiaresEnvioCodigoMail as fmEnvioC;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PhpParser\Node\Stmt\TryCatch;

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

           familiaresEnvioCodigoRecuperacion::dispatch($request->CorreoE,$codigoVerificacion);
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

        if ($Usuario->CodigoVerificacion != $codigo)
        {
            return response()->json(['message' => 'Codigo de verificacion incorrecto'], 401);
        }

        $Usuario->UsuarioVerificado="1";
        $Usuario->save();

        return response()->json(['message' => 'Usuario verificado'], 200);
    
    }

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

        $Usuario=fam::where(function($query) use ($credencial){
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
        if ($Usuario->UsuarioVerificado !="1")
        {
            return response()->json(['message' => 'Usuario no verificado'], 403);
        }
        $Usuario->TokenAcceso=Str::random(50);
        $Usuario->save();
        return response()->json(['message' => 'Login exitoso',
    'Usuario'=>[
        "IdUsuario"=>$Usuario->IdFamiliar,
        "TokenAcceso"=>$Usuario->TokenAcceso,
    ]], 200);

    }

    //metodo para enviar el codigo de recuperarcion al correo electronico
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

        $Usuario=fam::where('CorreoE',$correo)->first();
        if (!$Usuario)
        {
            return response()->json(['message' => 'Usuario No encontrado'], 404);
        }

        $codigoVerificacion = Str::random(10);
        $Usuario->CodigoVerificacion = $codigoVerificacion;
        $Usuario->save();
        familiaresEnvioCodigoRecuperacion::dispatch($correo,$codigoVerificacion);
        DB::commit();
        return response()->json(['message' => 'Correo de recuperacion enviado'], 200);
    }catch(\Exception $e){
        DB::rollBack();
        return response()->json(['message' => $e->getMessage()], 500);
    }
        
    }

    //metodo para verificar el codigo de recuperacion
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

        $Usuario=fam::where('CorreoE', $correo)->first();
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

        $Usuario=fam::where('CorreoE', $correo)->first();
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

    //Apartados relacionados a la tabla de cuidadores

    public function agregarCuidador(Request $request)
    {
        try{

            $request->validate([
                'IdFamiliar'=>['Required'],
                'TokenAcceso'=>['Required'],
                'Nombre'=>["Required","string","max:100"],
                'ApellidoP'=>["Required","string","max:100"],
                'ApellidoM'=>["nullable","string","max:100"],
                'Direccion'=>["nullable","string","max:250"],
                'Telefono1'=>["nullable","numeric","digits:10"],
                'Telefono2'=>["nullable","numeric","digits:10"],
                'CorreoE'=>["nullable","string","max:100","email","unique:cuidadores,CorreoE"],
                'Usuario'=>["Required","max:50","string","unique:cuidadores,Usuario"],
                'Contrasena'=>["Required","min:3","max:20"],
            ]);

        }catch(\Illuminate\Validation\ValidationException $e){
           $firstError = collect($e->errors())->flatten()->first();
              return response()->json(['error' => $firstError], 422);
        }

        try{
            DB::beginTransaction();

            $familiar=fam::where('IdFamiliar',$request->IdFamiliar)->first();

            if (!$familiar)
            {
                return response()->json(['message' => 'Familiar No encontrado'], 404);
            }

            if ($familiar->TokenAcceso != $request->TokenAcceso)
            {
                return response()->json(['message' => 'Token de acceso incorrecto'], 401);
            }

            $cuidador= new cuidadores();

            $cuidador->IdFamiliar=$request->IdFamiliar;
            $cuidador->Nombre=$request->Nombre;
            $cuidador->ApellidoP=$request->ApellidoP;
            $cuidador->ApellidoM=$request->ApellidoM;
            $cuidador->CorreoE=$request->CorreoE;
            $cuidador->Usuario=$request->Usuario;
            $cuidador->Contrasena=$request->Contrasena;
            $cuidador->TokenAcceso=Str::random(50);
            $cuidador->save();

            $cuidador->informacionContactoCuidador()->create([
                'IdCuidador' => $cuidador->IdCuidador,
                'Direccion' => $request->Direccion,
                'Telefono1' => $request->Telefono1,
                'Telefono2' => $request->Telefono2,
            ]);
            DB::commit();
            return response()->json(['Message'=>'Cuidador agregado'],201);


            
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


}
