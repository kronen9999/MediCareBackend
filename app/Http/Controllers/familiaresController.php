<?php

namespace App\Http\Controllers;

use App\Jobs\familiaresEnvioCodigoRecuperacion;
use App\Jobs\familiaresEnvioCodigoVerificacion;
use App\Models\cuidadores;
use App\Models\familiares as fam;
use App\Models\informacionContactoFamiliar as icf;
use App\Models\informacionContactoCuidador;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Mail\familiaresEnvioCodigoMail as fmEnvioC;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PhpParser\Node\Stmt\TryCatch;

use function PHPUnit\Framework\isEmpty;

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
            $informacionContactoFamiliar = $familiar->informacionContactoFamiliar()->create([
                'Direccion' => $request->Direccion,
                'Telefono1' => $request->Telefono1,
                'Telefono2' => $request->Telefono2,
            ]);
            $informacionContactoFamiliar->save();

           familiaresEnvioCodigoVerificacion::dispatch($request->CorreoE,$codigoVerificacion);
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

    

    /////////////////////////////////////////////////////////////////Metodos para el apartado de perfil del familiar//////////////////////////////////////////////////////////////////////////////////////

    //metodo para obtener la informacion del perfil del familiar
    public function ObtenerPerfil(Request $request)
    {
      try{

        $request->validate(['IdFamiliar'=>'required',
        'TokenAcceso'=>'required']);

      }catch(\Illuminate\Validation\ValidationException $e){
         $firstError = collect($e->errors())->flatten()->first();
            return response()->json(['error' => $firstError], 422);
      }

      $familiar=fam::where('IdFamiliar',$request->IdFamiliar)->first();
        if (!$familiar)
        {
            return response()->json(['message' => 'Familiar No encontrado'], 404);
        }
        if ($familiar->TokenAcceso != $request->TokenAcceso)
        {
            return response()->json(['message' => 'Token de acceso incorrecto'], 401);
        }
        
        
        $informacionFamiliar=$familiar->informacionContactoFamiliar()->first();
  
        if (!$informacionFamiliar) 
            {
            return response()->json(['message' => 'Información de contacto no encontrada'], 404);
            }
        return response()->json(["InformacionPersonal"=>[
            "Nombbre"=>$familiar->Nombre,
            "ApellidoP"=>$familiar->ApellidoP,
            "ApellidoM"=>$familiar->ApellidoM,
            "Direccion"=>$informacionFamiliar->Direccion,
            "Telefono1"=>$informacionFamiliar->Telefono1,
            "Telefono2"=>$informacionFamiliar->Telefono2,
        ],
    "InformacionCuenta"=>[
        "CorreoE"=>$familiar->CorreoE,
        "Usuario"=>$familiar->Usuario,
    ]],200);

    }

    //Metodo para actualizar la informacion personal del familiar

    public function ActualizarInformacionPersonal(Request $request)
    {
     try{
        $request->validate(["IdFamiliar"=>'required',
        "TokenAcceso"=>'required',
        "Nombre"=>["nullable","string","max:100"],
        "ApellidoP"=>["nullable","string","max:100"],
        "ApellidoM"=>["nullable","string","max:100"],
        "Direccion"=>["nullable","string","max:250"],
        "Telefono1"=>["nullable","numeric","digits:10"],
        "Telefono2"=>["nullable","numeric","digits:10"]]);
     }catch(\Illuminate\Validation\ValidationException $e){
        $firstError = collect($e->errors())->flatten()->first();
           return response()->json(['error' => $firstError], 422);
     }
     $familiar = fam::where('IdFamiliar',$request->IdFamiliar)->first();
        if (!$familiar)
        {
            return response()->json(['message' => 'Familiar No encontrado'], 404);
        }
        if ($familiar->TokenAcceso != $request->TokenAcceso)
        {
            return response()->json(['message' => 'Token de acceso incorrecto'], 401);
        }
        $informacionFamiliar=icf::where('IdFamiliar',$request->IdFamiliar)->first();
        if (!$informacionFamiliar)
        {
            return response()->json(['message' => 'Información de contacto no encontrada'], 404);
        }

        try{
            DB::beginTransaction();

            $familiar->Nombre=$request->Nombre;
            $familiar->ApellidoP=$request->ApellidoP;
            $familiar->ApellidoM=$request->ApellidoM;
            $familiar->save();
            $informacionFamiliar->Direccion=$request->Direccion;
            $informacionFamiliar->Telefono1=$request->Telefono1;
            $informacionFamiliar->Telefono2=$request->Telefono2;
            $informacionFamiliar->save();
            DB::commit();

            return response()->json(['message'=>'Información actualizada'],200);

        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    
    }

    //Metodo para actualizar la informacion de la cuenta del familiar
    public function ActualizarInformacionCuenta(Request $request)
    {
        try{
        $request->validate(["IdFamiliar"=>'required',
        "TokenAcceso"=>'required',
       "CorreoE"=>["required","email","max:100"],
       "Usuario"=>["nullable","string","max:50",]]);
     }catch(\Illuminate\Validation\ValidationException $e){
        $firstError = collect($e->errors())->flatten()->first();
           return response()->json(['error' => $firstError], 422);
     }

     $familiar=fam::where('IdFamiliar',$request->IdFamiliar)->first();
        if (!$familiar)
        {
            return response()->json(['message' => 'Familiar No encontrado'], 404);
        }
        if ($familiar->TokenAcceso != $request->TokenAcceso)
        {
            return response()->json(['message' => 'Token de acceso incorrecto'], 401);
        }

        try{
            DB::beginTransaction();

            if (!empty($request->CorreoE) && $request->CorreoE != $familiar->CorreoE) {
                if (fam::where('CorreoE', $request->CorreoE)->exists()) {
                    return response()->json(['error' => 'El correo ya está en uso por otro usuario'], 422);
                }
           $familiar->CorreoE = $request->CorreoE;
             }
             
            if ($request->Usuario != $familiar->Usuario&&!empty($request->Usuario)) {
                if (fam::where('Usuario', $request->Usuario)->exists()) {
                    return response()->json(['error' => 'El usuario ya está en uso por otro usuario'], 422);
                }
           $familiar->Usuario = $request->Usuario;
             }
           if (empty($request->Usuario)){
            $familiar->Usuario = null;
           }

            $familiar->save();
            DB::commit();

            return response()->json(['message'=>'Información actualizada'],200);

        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    //Metodo para actualizar la contrasena del familiar

    public function actualizarContrasena (Request $request)
    {
     
        try{
          $request->validate([
            "IdFamiliar"=>'required',
            "TokenAcceso"=>'required',
            "ContrasenaActual"=>["required","min:8","max:20"],
            "NuevaContrasena"=>["required","min:8","max:20"],
          ]);
        }catch(\Illuminate\Validation\ValidationException $e){
           $firstError = collect($e->errors())->flatten()->first();
              return response()->json(['error' => $firstError], 422);
        }

        $familiar=fam::where('IdFamiliar',$request->IdFamiliar)->first();
        if (!$familiar)
        {
            return response()->json(['message' => 'Familiar No encontrado'], 404);
        }

        if ($familiar->TokenAcceso != $request->TokenAcceso)
        {
            return response()->json(['message' => 'Token de acceso incorrecto'], 401);
        }

        if (!Hash::check($request->ContrasenaActual, $familiar->Contrasena))
        {
            return response()->json(['message' => 'Contraseña actual incorrecta'], 401);
        }

        try{
            DB::beginTransaction();

            $familiar->Contrasena = $request->NuevaContrasena;
            $familiar->save();
            DB::commit();

            return response()->json(['message'=>'Contraseña actualizada'],200);

        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    ///////////////////////////////////////////////////////////////////////////Rutas para Administrar los cuidadores//////////

    //Metodo para agregar a un cuidador

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
            return response()->json(['message'=>'Cuidador agregado'],201);


            
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }



}
