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
use App\Models\historialAdministracion;
use Carbon\Carbon;
use Exception;
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

    //Metodo para obtener el numero de pacientes y el numero de pacientes registrados

    public function obtenerAtributosGenerales(Request $request)
    {
           try{

            $request->validate([
                'IdFamiliar'=>'required',
                'TokenAcceso'=>'required',
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
        if($familiar->TokenAcceso!=$request->TokenAcceso)
        {
            return response()->json(['message' => 'Token de acceso incorrecto'], 401);
        }

        $numeroPacientes=$familiar->pacientes()->count();
        $numeroCuidadores=$familiar->cuidadores()->count();

        return response()->json([
            'NumeroPacientes'=>$numeroPacientes,
            'NumeroCuidadores'=>$numeroCuidadores,
        ],200);

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

    //Editar informacion de perfil del cuidador 

    public function editarCuidadorInformacionPerfil(Request $request)
    {
     try{
    $request->validate([
        "IdFamiliar"=>['Required'],
        "TokenAcceso"=>['Required'],
        "IdCuidador"=>['Required'],
        "Nombre"=>["nullable","string","max:100"],
        "ApellidoP"=>["nullable","string","max:100"],
        "ApellidoM"=>["nullable","string","max:100"],
        "Direccion"=>["nullable","string","max:250"],
        "Telefono1"=>["nullable","numeric","digits:10"],
        "Telefono2"=>["nullable","numeric","digits:10"],
    ]);
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

        $cuidador=cuidadores::where('IdCuidador',$request->IdCuidador)->first();
        if (!$cuidador)
        {
            return response()->json(['message' => 'Cuidador No encontrado'], 404);
        }
        if ($cuidador->IdFamiliar != $request->IdFamiliar)
        {
            return response()->json(['message' => 'El cuidador no pertenece a este familiar'], 403);
        }

        $informacionContactoCuidador=informacionContactoCuidador::where('IdCuidador',$request->IdCuidador)->first();
        if (!$informacionContactoCuidador)
        {
            return response()->json(['message' => 'Información de contacto no encontrada'], 404);
        }

        try{
            DB::beginTransaction();

            $cuidador->Nombre=$request->Nombre;
            $cuidador->ApellidoP=$request->ApellidoP;
            $cuidador->ApellidoM=$request->ApellidoM;
            $cuidador->save();
            $informacionContactoCuidador->Direccion=$request->Direccion;
            $informacionContactoCuidador->Telefono1=$request->Telefono1;
            $informacionContactoCuidador->Telefono2=$request->Telefono2;
            $informacionContactoCuidador->save();
            DB::commit();

            return response()->json(['message'=>'Información del cuidador actualizada'],200);

        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }

    }

    //Metodo para mostrar los cuidadores de un familiar

    public function obtenerCuidadores(Request $request)
    {
        try{
            $request->validate([
                'IdFamiliar'=>['Required'],
                'TokenAcceso'=>['Required'],
            ]);
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

         $cuidadores = cuidadores::where('IdFamiliar', $request->IdFamiliar)->get();

         if ($cuidadores->isEmpty()) {
             return response()->json(['message' => 'No se encontraron cuidadores'], 204);
         }

         

         $resultado = [];

         foreach ($cuidadores as $cuidador) {
             $infoContacto = informacionContactoCuidador::where('IdCuidador', $cuidador->IdCuidador)->first();
            $pacienteDelCuidador=$cuidador->pacientes()->exists();
            $paciente=$pacienteDelCuidador?"Asignado":"No Asignado";
             $resultado[] = [
                 'IdCuidador' => $cuidador->IdCuidador,
                 'Nombre' => $cuidador->Nombre,
                 'ApellidoP' => $cuidador->ApellidoP,
                 'ApellidoM' => $cuidador->ApellidoM,
                 'CorreoE' => $cuidador->CorreoE,
                 'Usuario' => $cuidador->Usuario,
                 'Direccion' => $infoContacto->Direccion,
                 'Telefono1' => $infoContacto->Telefono1,
                 'Telefono2' => $infoContacto->Telefono2,
                 'PacienteAsignado'=>$paciente,
             ];
         }

         return response()->json(['Cuidadores' => $resultado], 200);
    }

    //Metodo para obtener cuidadores sin asignacion

    public function obtenerCuidadoresNoAsignados(Request $request)
    {
   try{
    $request->validate(["IdFamiliar"=>["Required"],
    "TokenAcceso"=>["Required"]
    ]);
   }
   catch(\Illuminate\Validation\ValidationException $e)
   {
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

         $cuidadores = cuidadores::where('IdFamiliar', $request->IdFamiliar)->get();

         $resultado=[];
 
         foreach($cuidadores as $cuidador)
         {
         if ($cuidador->pacientes()->exists())
         {
       continue;
         }

         $resultado[]=[
            "IdCuidador"=>$cuidador->IdCuidador,
            "Nombre"=>$cuidador->Nombre,
            "ApellidoM"=>$cuidador->ApellidoM,
            "ApellidoP"=>$cuidador->ApellidoP
         ];
   

         }

         if (empty($resultado)) {
        return response()->json(['message' => 'No se encontraron cuidadores no asignados'], 204);
         }

    return response()->json(["CuidadoresNoAsignados" => $resultado], 200);

        }

    //Metodo para obtener un cuidador en especifico

    public function obtenerCuidador (Request $request)
    {
      try{

        $request->validate([
            'IdFamiliar'=>['Required'],
            'TokenAcceso'=>['Required'],
            'IdCuidador'=>['Required'],
        ]);

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

        $cuidador=$familiar->cuidadores()->where('IdCuidador',$request->IdCuidador)->first();
        if (!$cuidador)
        {
            return response()->json(['message' => 'Cuidador No encontrado'], 404);
        }
        if ($cuidador->IdFamiliar != $request->IdFamiliar)
        {
            return response()->json(['message' => 'El cuidador no pertenece a este familiar'], 403);
        }

        $informacionContactoCuidador=informacionContactoCuidador::where('IdCuidador',$request->IdCuidador)->first();
        if (!$informacionContactoCuidador)
        {
            return response()->json(['message' => 'Información de contacto no encontrada'], 404);
        }

        return response()->json([
            'IdCuidador' => $cuidador->IdCuidador,
            'Nombre' => $cuidador->Nombre,
            'ApellidoP' => $cuidador->ApellidoP,
            'ApellidoM' => $cuidador->ApellidoM,
            'CorreoE' => $cuidador->CorreoE,
            'Usuario' => $cuidador->Usuario,
            'Direccion' => $informacionContactoCuidador->Direccion,
            'Telefono1' => $informacionContactoCuidador->Telefono1,
            'Telefono2' => $informacionContactoCuidador->Telefono2,
        ],200);
    }

    //Metodo para actualizar la informacion de la cuenta del cuidador

    public function editarCuidadorInformacionCuenta(Request $request)
    {
        try{
            $request->validate([
                'IdFamiliar'=>['Required'],
                'TokenAcceso'=>['Required'],
                'IdCuidador'=>['Required'],
                'CorreoE'=>["nullable","string","max:100","email"],
                'Usuario'=>["required","string","max:50"],
            ]);
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
        $cuidador=$familiar->cuidadores()->where('IdCuidador',$request->IdCuidador)->first();
        if (!$cuidador)
        {
            return response()->json(['message' => 'Cuidador No encontrado'], 404);
        }
        if ($cuidador->IdFamiliar != $request->IdFamiliar)
        {
            return response()->json(['message' => 'El cuidador no pertenece a este familiar'], 403);
        }
        try{
            DB::beginTransaction();

            if (!empty($request->CorreoE) && $request->CorreoE != $cuidador->CorreoE) {
                if (cuidadores::where('CorreoE', $request->CorreoE)->exists()) {
                    return response()->json(['error' => 'El correo ya está en uso por otro usuario'], 422);
                }
           $cuidador->CorreoE = $request->CorreoE;
             }
             if (empty($request->CorreoE)){
                $cuidador->CorreoE = null;
               }
             
            if ($request->Usuario != $cuidador->Usuario) {
                if (cuidadores::where('Usuario', $request->Usuario)->exists()) {
                    return response()->json(['error' => 'El usuario ya está en uso por otro usuario'], 422);
                }
           $cuidador->Usuario = $request->Usuario;
             }
            $cuidador->save();
            DB::commit();

            return response()->json(['message'=>'Información del cuidador actualizada'],200);
    }
    catch(\Exception $e){
        DB::rollBack();
        return response()->json(['message' => $e->getMessage()], 500);
    }
    }

    //Metodo para cambiar la contrasena de un cuidador

    public function cambiarContrasenaCuidador(Request $request)
    {
  
     try{
        $request->validate([
            'IdFamiliar'=>['Required'],
            'TokenAcceso'=>['Required'],
            'IdCuidador'=>['Required'],
            'NuevaContrasena'=>["Required","min:8","max:20"],
        ]);
     }catch(\Illuminate\Validation\ValidationException $e)
     {
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
    
            $cuidador=$familiar->cuidadores()->where('IdCuidador',$request->IdCuidador)->first();
            if (!$cuidador)
            {
                return response()->json(['message' => 'Cuidador No encontrado'], 404);
            }
            if ($cuidador->IdFamiliar != $request->IdFamiliar)
            {
                return response()->json(['message' => 'El cuidador no pertenece a este familiar'], 403);
            }
    
            try{
                DB::beginTransaction();
    
                $cuidador->Contrasena = $request->NuevaContrasena;
                $cuidador->save();
                DB::commit();
    
                return response()->json(['message'=>'Contraseña del cuidador actualizada'],200);
    
            }catch(\Exception $e){
                DB::rollBack();
                return response()->json(['message' => $e->getMessage()], 500);
            }

    }

    //Metodo para eliminar un cuidador
    public function eliminarCuidador(Request $request)
    {
          try{
        $request->validate([
            'IdFamiliar'=>['Required'],
            'TokenAcceso'=>['Required'],
            'IdCuidador'=>['Required'],
        ]);
     }catch(\Illuminate\Validation\ValidationException $e)
     {
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
    
            $cuidador=$familiar->cuidadores()->where('IdCuidador',$request->IdCuidador)->first();
            if (!$cuidador)
            {
                return response()->json(['message' => 'Cuidador No encontrado'], 404);
            }
            if ($cuidador->IdFamiliar != $request->IdFamiliar)
            {
                return response()->json(['message' => 'El cuidador no pertenece a este familiar'], 403);
            }
            try{
                DB::beginTransaction();

                if($cuidador->pacientes()->exists()){
                    $paciente=$cuidador->pacientes()->first();
                    $paciente->IdCuidador=null;
                    $paciente->save();
                 }

                 $cuidador->delete();
                DB::commit();

                return response()->json(["message"=>"Cuidador eliminado"],200);

            }catch(Exception $e)
            {
                DB::rollBack();
             return response()->json(['message' => $e->getMessage()], 500);
            }
    }

    /////////////////////////////////////////////Metodos para Administrar pacientes//////////////////////////////////////////////////////////////////////////////////////
   
    //Metodo para agregar un paciente

    public function agregarPaciente(Request $request)
    {
     
   try{
        $request->validate([
            'IdFamiliar'=>['Required'],
            'TokenAcceso'=>['Required'],
            'Nombre'=>["Required","string","max:100"],
            'ApellidoP'=>["Required","string","max:100"],
            'ApellidoM'=>["nullable","string","max:100"],
            'Padecimiento'=>["nullable","string","max:100"],
            'Direccion'=>["nullable","string","max:250"],
            'Telefono1'=>["nullable","numeric","digits:10"],
            'Telefono2'=>["nullable","numeric","digits:10"]

        ]);
     }catch(\Illuminate\Validation\ValidationException $e)
     {
     $firstError = collect($e->errors())->flatten()->first();
        return response()->json(['error' => $firstError], 422);
     }

     try{
        DB::beginTransaction();
           $familiar = fam::where('IdFamiliar',$request->IdFamiliar)->first();
            if (!$familiar)
            {
                return response()->json(['message' => 'Familiar No encontrado'], 404);
            }
            if ($familiar->TokenAcceso != $request->TokenAcceso)
            {
                return response()->json(['message' => 'Token de acceso incorrecto'], 401);
            }
    
            $paciente=$familiar->pacientes()->create([
                'Nombre'=>$request->Nombre,
                'ApellidoP'=>$request->ApellidoP,
                'ApellidoM'=>$request->ApellidoM,
                'Padecimiento'=>$request->Padecimiento,
            ]);

            $paciente->save();
           
            $informacionContactoPaciente=$paciente->informacionContactoPaciente()->create([
                'IdPaciente'=>$paciente->IdPaciente,
                'Direccion'=>$request->Direccion,
                'Telefono1'=>$request->Telefono1,
                'Telefono2'=>$request->Telefono2,
            ]);
            $informacionContactoPaciente->save();

            DB::commit();

            return response()->json(['message'=>'Paciente agregado'],201);
            

        

     }catch(\Exception $e){
        DB::rollBack();

        return response()->json(['message' => $e->getMessage()], 500);
     }

         
    }

    //Metodo para Mostar los pacientes de un familiar

    public function obtenerPacientes(Request $request)
    {
    
     try{

        $request->validate([
            'IdFamiliar'=>['Required'],
            'TokenAcceso'=>['Required'],
        ]);

     }catch(\Illuminate\Validation\ValidationException $e)
     {
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

        $pacientes = $familiar->pacientes()->get();
        if ($pacientes->isEmpty()) {
            return response()->json(['message' => 'No se encontraron pacientes'], 204);
        }
        $resultado = [];
        foreach ($pacientes as $paciente) {
            $infoContacto = $paciente->informacionContactoPaciente()->first();
            $cuidador =$paciente->cuidadores()->first();
            $resultado[] = [
                'IdPaciente' => $paciente->IdPaciente,
                'Nombre' => $paciente->Nombre,
                'ApellidoP' => $paciente->ApellidoP,
                'ApellidoM' => $paciente->ApellidoM,
                'Padecimiento' => $paciente->Padecimiento,
                'Direccion' => $infoContacto->Direccion,
                'Telefono1' => $infoContacto->Telefono1,
                'Telefono2' => $infoContacto->Telefono2,
                'IdCuidador' => $paciente->IdCuidador,
                'NombreCuidador' => $cuidador ? $cuidador->Nombre : null,
                'ApellidoPCuidador' => $cuidador ? $cuidador->ApellidoP : null,
                'ApellidoMCuidador' => $cuidador ? $cuidador->ApellidoM : null,
            ];
        }

        return response()->json(['Pacientes' => $resultado], 200);
        
    }

    //Metodo para obtener un paciente en especifico

    public function obtenerPaciente(Request $request)
    {
    
     try{
        $request->validate([
            'IdFamiliar'=>['Required'],
            'TokenAcceso'=>['Required'],
            'IdPaciente'=>['Required'],
        ]);
     }catch(\Illuminate\Validation\ValidationException $e)
     {
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

        $paciente=$familiar->pacientes()->where('IdPaciente',$request->IdPaciente)->first();
        if (!$paciente)
        {
            return response()->json(['message' => 'Paciente No encontrado'], 404);
        }
        if ($paciente->IdFamiliar != $request->IdFamiliar)
        {
            return response()->json(['message' => 'El paciente no pertenece a este familiar'], 403);
        }

        $informacionContactoPaciente=$paciente->informacionContactoPaciente()->first();
        if (!$informacionContactoPaciente)
        {
            return response()->json(['message' => 'Información de contacto no encontrada'], 404);
        }

        return response()->json([
            'IdPaciente' => $paciente->IdPaciente,
            'Nombre' => $paciente->Nombre,
            'ApellidoP' => $paciente->ApellidoP,
            'ApellidoM' => $paciente->ApellidoM,
            'Padecimiento' => $paciente->Padecimiento,
            'Direccion' => $informacionContactoPaciente->Direccion,
            'Telefono1' => $informacionContactoPaciente->Telefono1,
            'Telefono2' => $informacionContactoPaciente->Telefono2,
            'IdCuidador' => $paciente->IdCuidador,
        ],200);

    }
     
    //Metodo para editar la informacion del paciente

    public function editarPaciente(Request $request)
    {

      try{
        $request->validate([
            'IdFamiliar'=>['Required'],
            'TokenAcceso'=>['Required'],
            'IdPaciente'=>['Required'],
            'Nombre'=>["Required","string","max:100"],
            'ApellidoP'=>["nullable","string","max:100"],
            'ApellidoM'=>["nullable","string","max:100"],
            'Padecimiento'=>["nullable","string","max:100"],
            'Direccion'=>["nullable","string","max:250"],
            'Telefono1'=>["nullable","numeric","digits:10"],
            'Telefono2'=>["nullable","numeric","digits:10"]
        ]);
      }catch(\Illuminate\Validation\ValidationException $e)
      {
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

        $paciente=$familiar->pacientes()->where('IdPaciente',$request->IdPaciente)->first();
        if (!$paciente)
        {
            return response()->json(['message' => 'Paciente No encontrado'], 404);
        }
        if ($paciente->IdFamiliar != $request->IdFamiliar)
        {
            return response()->json(['message' => 'El paciente no pertenece a este familiar'], 403);
        }

        $informacionContactoPaciente=$paciente->informacionContactoPaciente()->first();
        if (!$informacionContactoPaciente)
        {
            return response()->json(['message' => 'Información de contacto no encontrada'], 404);
        }

        try{
            DB::beginTransaction();

            $paciente->Nombre=$request->Nombre;
            $paciente->ApellidoP=$request->ApellidoP;
            $paciente->ApellidoM=$request->ApellidoM;
            $paciente->Padecimiento=$request->Padecimiento;
            $paciente->save();
            $informacionContactoPaciente->Direccion=$request->Direccion;
            $informacionContactoPaciente->Telefono1=$request->Telefono1;
            $informacionContactoPaciente->Telefono2=$request->Telefono2;
            $informacionContactoPaciente->save();
            DB::commit();

            return response()->json(['message'=>'Información del paciente actualizada'],200);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }

    }

    //Metodo para asignar un cuidador a un paciente

    public function asignarCuidadorPaciente(Request $request)
    {

    try{

       $request->validate([
        'IdFamiliar'=>['Required'],
        'TokenAcceso'=>['Required'],
        'IdPaciente'=>['Required'],
        'IdCuidador'=>['Required'],
       ]);

    }catch(\Illuminate\Validation\ValidationException $e)
    {
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

        $paciente=$familiar->pacientes()->where('IdPaciente',$request->IdPaciente)->first();
        if (!$paciente)
        {
            return response()->json(['message' => 'Paciente No encontrado'], 404);
        }
        if ($paciente->IdFamiliar != $request->IdFamiliar)
        {
            return response()->json(['message' => 'El paciente no pertenece a este familiar'], 403);
        }

        $cuidador=$familiar->cuidadores()->where('IdCuidador',$request->IdCuidador)->first();
        if (!$cuidador)
        {
            return response()->json(['message' => 'Cuidador No encontrado'], 404);
        }
        if ($cuidador->IdFamiliar != $request->IdFamiliar)
        {
            return response()->json(['message' => 'El cuidador no pertenece a este familiar'], 403);
        }
        
        if ($cuidador->pacientes()->first())
            {
                return response()->json(['message' => 'El cuidador ya está asignado a un paciente'], 409);
            }       

        if ($paciente->IdCuidador!=null)
            {
              return response()->json(['message' => 'El paciente ya tiene un cuidador asignado'], 409);
            }    

        try{
            DB::beginTransaction();

           $paciente->IdCuidador=$request->IdCuidador;
           $paciente->save();

            DB::commit();

            return response()->json(['message'=>'Cuidador asignado al paciente'],201);

        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }

    }

    //Metodo para desasignar un cuidador de un paciente

    public function desasignarCuidador(Request $request)
    {
      try{
        $request->validate([
            'IdFamiliar'=>['Required'],
            'TokenAcceso'=>['Required'],
            'IdPaciente'=>['Required'],
        ]);
      }catch(\Illuminate\Validation\ValidationException $e)
      {
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

        $paciente=$familiar->pacientes()->where('IdPaciente',$request->IdPaciente)->first();
        if (!$paciente)
        {
            return response()->json(['message' => 'Paciente No encontrado'], 404);
        }
        if ($paciente->IdFamiliar != $request->IdFamiliar)
        {
            return response()->json(['message' => 'El paciente no pertenece a este familiar'], 403);
        }

        if ($paciente->IdCuidador==null)
            {
              return response()->json(['message' => 'El paciente no tiene un cuidador asignado'], 409);
            }    

        try{
            DB::beginTransaction();

           $paciente->IdCuidador=null;
           $paciente->save();

            DB::commit();

            return response()->json(['message'=>'Cuidador desasignado del paciente'],200);

        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    //Metodo para eliminar un paciente
    public function eliminarPaciente(Request $request)
    {
        try{
        $request->validate([
            'IdFamiliar'=>['Required'],
            'TokenAcceso'=>['Required'],
            'IdPaciente'=>['Required'],
        ]);
      }catch(\Illuminate\Validation\ValidationException $e)
      {
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

        $paciente=$familiar->pacientes()->where('IdPaciente',$request->IdPaciente)->first();
        if (!$paciente)
        {
            return response()->json(['message' => 'Paciente No encontrado'], 404);
        }
        if ($paciente->IdFamiliar != $request->IdFamiliar)
        {
            return response()->json(['message' => 'El paciente no pertenece a este familiar'], 403);
        }
        try{
            DB::beginTransaction();

           $paciente->delete();

            DB::commit();

            return response()->json(['message'=>'Paciente eliminado'],200);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    ///////////////////////////////////Metodos para los medicamentos del paciente////////////////////

    public function agregarMedicamentoHorario(Request $request)
    {
try{
        $request->validate([
            'IdFamiliar'=>['Required'],
            'TokenAcceso'=>['Required'],
            'IdPaciente'=>['Required'],
            'NombreM'=>['Required','max:100'],
            'DescripcionM'=>['nullable','max:250'],
            'TipoMedicamento'=>['Required','max:100'],
            'HoraPrimeraDosis'=>['Required','date_format:Y-m-d H:i:s'],
            'IntervaloHoras'=>['Required','integer','min:0','max:12'],
            'IntervaloMinutos'=>['Required','integer','min:0','max:60'],
            'PrimerRecordatorio'=>["Required"],
            'Dosis'=>['Required','integer','min:1'],
            'UnidadDosis'=>['Required','max:50'],
            'Notas'=>['nullable','max:250'],
        ]);
      }catch(\Illuminate\Validation\ValidationException $e)
      {
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

        $paciente=$familiar->pacientes()->where('IdPaciente',$request->IdPaciente)->first();
        if (!$paciente)
        {
            return response()->json(['message' => 'Paciente No encontrado'], 404);
        }
        if ($paciente->IdFamiliar != $request->IdFamiliar)
        {
            return response()->json(['message' => 'El paciente no pertenece a este familiar'], 403);
        }
        try{
            DB::beginTransaction();
         $medicamento=$paciente->medicamentos()->create([
            'NombreM'=>$request->NombreM,
            'DescripcionM'=>$request->DescripcionM,
            'TipoMedicamento'=>$request->TipoMedicamento,
            'MedicamentoActivo'=>1,
        ]);
        $medicamento->save();   

        $horarioMedicamento=$medicamento->horariosMedicamentos()->create([
            'HoraPrimeraDosis'=>$request->HoraPrimeraDosis,
            'IntervaloHoras'=>$request->IntervaloHoras,
            'IntervaloMinutos'=>$request->IntervaloMinutos,
            'Dosis'=>$request->Dosis,
            'UnidaDosis'=>$request->UnidadDosis,
            'Notas'=>$request->Notas,
        ]);
        $horarioMedicamento->save();

        if ($request->PrimerRecordatorio=="SinRecordatorio")
        {
            $fechaInicio=$request->HoraPrimeraDosis;
            $intervaloHoras=(int)$request->IntervaloHoras;
            $intervaloMinutos=(int)$request->IntervaloMinutos;

            $fecha= Carbon::createFromFormat('Y-m-d H:i:s',$fechaInicio);

            $fechaSiguiente = $fecha->copy()->addHours($intervaloHoras)->addMinutes($intervaloMinutos);

            $siguienteDosis = $fechaSiguiente->format('Y-m-d H:i:s');

            $historialMedicamento=$horarioMedicamento->historialAdministracion()->create([
                'FechaProgramada'=>$siguienteDosis,
                'HoraAdministracion'=>null,
                'Estado'=>'No Administrado',
                'Administro'=>null,
                'IdFamiliar'=>$request->IdFamiliar,
                'IdCuidador'=>$paciente->IdCuidador,
                'NombreM'=>$medicamento->NombreM,
                'NombreP'=>$paciente->Nombre,
                'Dosis'=>$horarioMedicamento->Dosis,
                'UnidadDosis'=>$horarioMedicamento->UnidaDosis,
                'Notas'=>$horarioMedicamento->Notas,
            ]);
            
            $historialMedicamento->save();
            DB::commit();
            return response()->json(["message"=>"Medicamento agregado exitosamente y  se ha agregado el primer recordatorio del medicamento",
        "FechaProgramada"=>$siguienteDosis],200);
        }
        else if ($request->PrimerRecordatorio=="ConRecordatorio") {
             $historialMedicamento=$horarioMedicamento->historialAdministracion()->create([
                'FechaProgramada'=>$request->HoraPrimeraDosis,
                'HoraAdministracion'=>null,
                'Estado'=>'No Administrado',
                'Administro'=>null,
                'IdFamiliar'=>$request->IdFamiliar,
                'IdCuidador'=>$paciente->IdCuidador,
                'NombreM'=>$medicamento->NombreM,
                'NombreP'=>$paciente->Nombre,
                'Dosis'=>$horarioMedicamento->Dosis,
                'UnidadDosis'=>$horarioMedicamento->UnidaDosis,
                'Notas'=>$horarioMedicamento->Notas,
            ]);
            
            $historialMedicamento->save();
            DB::commit();
            return response()->json(["message"=>"Medicamento agregado exitosamente y  se ha agregado el primer recordatorio del medicamento",
        "FechaProgramada"=>$request->HoraPrimeraDosis],200);
    
        }        
        {
            DB::rollBack();
            return response()->json(['message' => 'Opción de primer recordatorio no válida'], 400);
        }
        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
          }
       }

       public function obtenerMedicamentos(Request $request)
       {
        try{
            $request->validate([
                'IdFamiliar'=>['Required'],
                'TokenAcceso'=>['Required'],
                'IdPaciente'=>['Required'],
            ]);
          }catch(\Illuminate\Validation\ValidationException $e)
          {
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

            $paciente=$familiar->pacientes()->where('IdPaciente',$request->IdPaciente)->first();
            if (!$paciente)
            {
                return response()->json(['message' => 'Paciente No encontrado'], 404);
            }
            if ($paciente->IdFamiliar != $request->IdFamiliar)
            {
                return response()->json(['message' => 'El paciente no pertenece a este familiar'], 403);
            }

            $medicamentos=$paciente->medicamentos;
            if ($medicamentos->isEmpty())
            {
                return response()->json(["message"=>"Sin medicamentos registrados"],204);
            }
            $resultado=[]; 
            foreach($medicamentos as $medicamento)
            {
                $resultado[]=[
                    'IdMedicamento'=>$medicamento->IdMedicamento,
                    'NombreM'=>$medicamento->NombreM,
                    'DescripcionM'=>$medicamento->DescripcionM,
                    'TipoMedicamento'=>$medicamento->TipoMedicamento,
                    'MedicamentoActivo'=>$medicamento->MedicamentoActivo,
                ];
            }

            

            return response()->json(['Medicamentos'=>$resultado],200);
        }

       public function  obtenerMedicamento(Request $request)
       {
            try{
            $request->validate([
                'IdFamiliar'=>['Required'],
                'TokenAcceso'=>['Required'],
                'IdPaciente'=>['Required'],
                'IdMedicamento'=>['Required']
            ]);
          }catch(\Illuminate\Validation\ValidationException $e)
          {
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

            $paciente=$familiar->pacientes()->where('IdPaciente',$request->IdPaciente)->first();
            if (!$paciente)
            {
                return response()->json(['message' => 'Paciente No encontrado'], 404);
            }
            if ($paciente->IdFamiliar != $request->IdFamiliar)
            {
                return response()->json(['message' => 'El paciente no pertenece a este familiar'], 403);
            }
            $medicamento=$paciente->medicamentos()->where("IdMedicamento",$request->IdMedicamento)->first();
            if (!$medicamento)
            {
                return response()->json(["message"=>"Medicamento no encontrado"],404);
            }

            $informacionHorarioMedicamento=$medicamento->horariosMedicamentos()->first();
            if (!$informacionHorarioMedicamento)
            {
                return response()->json(["message"=>"Horario de medicamento no encontrado"],404);
            }


            return response()->json([
                'IdMedicamento'=>$medicamento->IdMedicamento,
                'NombreM'=>$medicamento->NombreM,
                'DescripcionM'=>$medicamento->DescripcionM,
                'TipoMedicamento'=>$medicamento->TipoMedicamento,
                'IntervaloHoras'=>$informacionHorarioMedicamento->IntervaloHoras,
                'IntervaloMinutos'=>$informacionHorarioMedicamento->IntervaloMinutos,
                'Dosis'=>$informacionHorarioMedicamento->Dosis,
                'UnidadDosis'=>$informacionHorarioMedicamento->UnidaDosis,
                'Notas'=>$informacionHorarioMedicamento->Notas,
            ],200);
       }

       public function editarMedicamento(Request $request)
       {
 try{
            $request->validate([
                'IdFamiliar'=>['Required'],
                'TokenAcceso'=>['Required'],
                'IdPaciente'=>['Required'],
                'IdMedicamento'=>['Required'],
                'NombreM'=>['Required','max:100'],
                'DescripcionM'=>['nullable','max:250'],
                'TipoMedicamento'=>['nullable','max:100'],
                'UnidadDosis'=>['Required','max:50'],
                'Notas'=>['nullable','max:250'],
            ]);
          }catch(\Illuminate\Validation\ValidationException $e)
          {
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

            $paciente=$familiar->pacientes()->where('IdPaciente',$request->IdPaciente)->first();
            if (!$paciente)
            {
                return response()->json(['message' => 'Paciente No encontrado'], 404);
            }
            if ($paciente->IdFamiliar != $request->IdFamiliar)
            {
                return response()->json(['message' => 'El paciente no pertenece a este familiar'], 403);
            }
            $medicamento=$paciente->medicamentos()->where("IdMedicamento",$request->IdMedicamento)->first();
            if (!$medicamento)
            {
                return response()->json(["message"=>"Medicamento no encontrado"],404);
            }
            $horarioMedicamento=$medicamento->horariosMedicamentos()->first();
            try{
                DB::beginTransaction();

                $medicamento->NombreM=$request->NombreM;
                $medicamento->DescripcionM=$request->DescripcionM;
                $medicamento->TipoMedicamento=$request->TipoMedicamento;
                $medicamento->save();
                $horarioMedicamento->UnidaDosis=$request->UnidadDosis;
                $horarioMedicamento->Notas=$request->Notas;
                $horarioMedicamento->save();

                DB::commit();

                return response()->json(['message'=>'Informacion del medicamento actualizada'],200);

            }catch(Exception $e){
                DB::rollBack();
                return response()->json(['message' => $e->getMessage()], 500);
            }

       }

    public function editarHorarioMedicamento(Request $request)
    {
        try{
            $request->validate([
                'IdFamiliar'=>['Required'],
                'TokenAcceso'=>['Required'],
                'IdPaciente'=>['Required'],
                'IdMedicamento'=>['Required'],
                'IntervaloHoras'=>['Required','integer','min:0','max:12'],
                'IntervaloMinutos'=>['Required','integer','min:0','max:60'],
                'Dosis'=>['Required','integer','min:1'],
            ]);
          }catch(\Illuminate\Validation\ValidationException $e)
          {
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

            $paciente=$familiar->pacientes()->where('IdPaciente',$request->IdPaciente)->first();
            if (!$paciente)
            {
                return response()->json(['message' => 'Paciente No encontrado'], 404);
            }
            if ($paciente->IdFamiliar != $request->IdFamiliar)
            {
                return response()->json(['message' => 'El paciente no pertenece a este familiar'], 403);
            }
            $medicamento=$paciente->medicamentos()->where("IdMedicamento",$request->IdMedicamento)->first();
            if (!$medicamento)
            {
                return response()->json(["message"=>"Medicamento no encontrado"],404);
            }
            $horarioMedicamento=$medicamento->horariosMedicamentos()->first();
            if (!$horarioMedicamento)
            {
                return response()->json(["message"=>"Horario de medicamento no encontrado"],404);
            }
            try{
                DB::beginTransaction();
                
                     
                     $horarioMedicamento->IntervaloHoras=$request->IntervaloHoras;
                     $horarioMedicamento->IntervaloMinutos=$request->IntervaloMinutos;
                     $horarioMedicamento->Dosis=$request->Dosis;
                $horarioMedicamento->save();
                 DB::commit();
                return response()->json(['message'=>'Informacion del horario del medicamento actualizada'],200);
            }catch(Exception $e){
                DB::rollBack();
                return response()->json(['message' => $e->getMessage()], 500);
            }
            
    }
             public function habilitarMedicamento(Request $request)
            {
                try{
            $request->validate([
                'IdFamiliar'=>['Required'],
                'TokenAcceso'=>['Required'],
                'IdPaciente'=>['Required'],
                'IdMedicamento'=>['Required'],
                'HoraCalculo'=>['Required','date_format:Y-m-d H:i:s'],
            ]);
          }catch(\Illuminate\Validation\ValidationException $e)
          {
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

            $paciente=$familiar->pacientes()->where('IdPaciente',$request->IdPaciente)->first();
            if (!$paciente)
            {
                return response()->json(['message' => 'Paciente No encontrado'], 404);
            }
            if ($paciente->IdFamiliar != $request->IdFamiliar)
            {
                return response()->json(['message' => 'El paciente no pertenece a este familiar'], 403);
            }
            $medicamento=$paciente->medicamentos()->where("IdMedicamento",$request->IdMedicamento)->first();
            if (!$medicamento)
            {
                return response()->json(["message"=>"Medicamento no encontrado"],404);
            }
            $horarioMedicamento=$medicamento->horariosMedicamentos()->first();
            try{
               DB::beginTransaction();
            $medicamento->MedicamentoActivo=1;
            $medicamento->save();

               $fechaRecordatorioDate=Carbon::createFromFormat("Y-m-d H:i:s",$request->HoraCalculo);
          

                 $nuevoRegistro= $horarioMedicamento->historialAdministracion()->create([
                'FechaProgramada'=>$fechaRecordatorioDate,
                'HoraAdministracion'=>null,
                'Estado'=>'No Administrado',
                'Administro'=>null,
                'IdFamiliar'=>$request->IdFamiliar,
                'IdCuidador'=>$paciente->IdCuidador,
                'NombreM'=>$medicamento->NombreM,
                'NombreP'=>$paciente->Nombre,
                'Dosis'=>$horarioMedicamento->Dosis,
                'UnidadDosis'=>$horarioMedicamento->UnidaDosis,
                'Notas'=>$horarioMedicamento->Notas,
            ]);
               
             DB::commit();

            return response()->json(['message'=>'Medicamento habilitado ,el siguiente recordatorio se ha registrado correctamente',"FechaSiguienteDosis"=>$fechaRecordatorioDate],200);
            }catch(Exception $e)
            {
                DB::rollBack();
            return response()->json(["message"=>$e->getMessage()]);
            }
            
            }
            public function desabilitarMedicamento(Request $request)
            {
                try{
            $request->validate([
                'IdFamiliar'=>['Required'],
                'TokenAcceso'=>['Required'],
                'IdPaciente'=>['Required'],
                'IdMedicamento'=>['Required'],
            ]);
          }catch(\Illuminate\Validation\ValidationException $e)
          {
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

            $paciente=$familiar->pacientes()->where('IdPaciente',$request->IdPaciente)->first();
            if (!$paciente)
            {
                return response()->json(['message' => 'Paciente No encontrado'], 404);
            }
            if ($paciente->IdFamiliar != $request->IdFamiliar)
            {
                return response()->json(['message' => 'El paciente no pertenece a este familiar'], 403);
            }
            $medicamento=$paciente->medicamentos()->where("IdMedicamento",$request->IdMedicamento)->first();
            if (!$medicamento)
            {
                return response()->json(["message"=>"Medicamento no encontrado"],404);
            }
            try{
               DB::beginTransaction();
            $medicamento->MedicamentoActivo=0;
            $medicamento->save();

               
             DB::commit();

            return response()->json(['message'=>'Medicamento Desabilitado ,no se generaran mas recordatorios'],200);
            }catch(Exception $e)
            {
                DB::rollBack();
            return response()->json(["message"=>$e->getMessage()]);
            }
            
            }
    /////////////////////////////////////////Metodos de historial de administracion de medicamentos//////////////////////////////////////////////////////

    public function obtenerProximosRecordatorios(Request $request)
    {
    try{
        $request->validate([
            'IdFamiliar'=>['Required'],
            'TokenAcceso'=>['Required'],
        ]);
    }catch(\Illuminate\Validation\ValidationException $e)
    {
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
            $recordatorios=$familiar->historial()->where('Estado','=',"No Administrado")->orderBy("FechaProgramada")->get();

            $recordatoriosProximos=[];

            foreach($recordatorios as $recordatorio)
            {
                $Cuidador=$familiar->cuidadores()->where('IdCuidador',$recordatorio->IdCuidador)->first();
                $recordatoriosProximos[]=[
                    "IdHistorial"=>$recordatorio->idHistorial,
                    "FechaProgramada"=>$recordatorio->FechaProgramada,
                    "NombreM"=>$recordatorio->NombreM,
                    "NombreP"=>$recordatorio->NombreP,
                    "Dosis"=>$recordatorio->Dosis,
                    "UnidadDosis"=>$recordatorio->UnidadDosis,
                    "Notas"=>$recordatorio->Notas,
                    "NombreCuidador"=>$Cuidador?$Cuidador->Nombre:null
                ];
            }

            if ($recordatoriosProximos==[])
            {
                return response()->json(['message'=>'No hay recordatorios pendientes'],204);
            }
            else {
                return response()->json(['Recordatorios'=>$recordatoriosProximos],200);
            }

}


public function administrarMedicamento(Request $request)
{
     try{
        $request->validate([
            'IdFamiliar'=>['Required'],
            'TokenAcceso'=>['Required'],
            'IdHistorial'=>['Required'],
            'FechaAdministracion'=>['Required','date_format:Y-m-d H:i:s']
        ]);
    }catch(\Illuminate\Validation\ValidationException $e)
    {
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
    $historialMedicamento=$familiar->historial()->where("idHistorial","=",$request->IdHistorial)->first();
    if (!$historialMedicamento)
    {
      return response()->json(["message"=>"Registro no encontrado"],404);
    }
    

      
    try{
        DB::beginTransaction();
     $medicamentoHorario=$historialMedicamento->horario()->first();  
     $medicamento=$medicamentoHorario->medicamento()->first();
     $paciente=$medicamento->paciente()->first();

     $fecha= Carbon::createFromFormat('Y-m-d H:i:s',$request->FechaAdministracion);
     $fechaAnterior= Carbon::createFromFormat('Y-m-d H:i:s',$historialMedicamento->FechaProgramada);

     if ($fecha<$fechaAnterior)
     {
        return response()->json(["error"=>"No se puede dar el medicamento antes de la fecha y hora indicadas"],422);
     }
     
     
    if ($medicamento->MedicamentoActivo==1)
    {  
        
         $historialMedicamento->Administro="Familiar";
         $historialMedicamento->Estado="Administrado";
         $historialMedicamento->HoraAdministracion=$request->FechaAdministracion;
         $historialMedicamento->save();
         

            $fechaSiguiente = $fecha->copy()->addHours((int)$medicamentoHorario->IntervaloHoras)->addMinutes((int)$medicamentoHorario->IntervaloMinutos);

            $siguienteDosis = $fechaSiguiente->format('Y-m-d H:i:s');

           $nuevoRegistro= $medicamentoHorario->historialAdministracion()->create([
                'FechaProgramada'=>$siguienteDosis,
                'HoraAdministracion'=>null,
                'Estado'=>'No Administrado',
                'Administro'=>null,
                'IdFamiliar'=>$request->IdFamiliar,
                'IdCuidador'=>$paciente->IdCuidador,
                'NombreM'=>$medicamento->NombreM,
                'NombreP'=>$paciente->Nombre,
                'Dosis'=>$medicamentoHorario->Dosis,
                'UnidadDosis'=>$medicamentoHorario->UnidaDosis,
                'Notas'=>$medicamentoHorario->Notas,
            ]);
            DB::commit();

            return response()->json(["message"=>"Administracion registrada,se ha generado el siguiente recordatorio de la dosis",
        "FechaSiguienteDosis"=>$nuevoRegistro->FechaProgramada],200);
    }
    else {
        
         $historialMedicamento->Administro="Familiar";
         $historialMedicamento->Estado="Administrado";
         $historialMedicamento->HoraAdministracion=$request->FechaAdministracion;
         $historialMedicamento->save();
         DB::commit();
      return response()->json([
        "message" => "Administracion registrada, debido a que el medicamento no está activo no se generará la siguiente dosis",
        "FechaSiguienteDosis"=>null
    ], 200);
    }

    
    }catch(Exception $e){
     DB::rollBack();
     return response()->json(["message"=>$e->getMessage()],500);
    }
    
    
}

public function cancelarAdministracionMedicamento(Request $request){
    try{
        $request->validate([
            'IdFamiliar'=>['Required'],
            'TokenAcceso'=>['Required'],
            'IdHistorial'=>['Required'],
            'FechaCancelacion'=>['Required','date_format:Y-m-d H:i:s']
        ]);
    }catch(\Illuminate\Validation\ValidationException $e)
    {
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
    $historialMedicamento=$familiar->historial()->where("idHistorial","=",$request->IdHistorial)->first();
    if (!$historialMedicamento)
    {
      return response()->json(["message"=>"Registro no encontrado"],404);
    }
       
    try{
        DB::beginTransaction();
     $medicamentoHorario=$historialMedicamento->horario()->first();  
     $medicamento=$medicamentoHorario->medicamento()->first();
     $paciente=$medicamento->paciente()->first();

     $fecha= Carbon::createFromFormat('Y-m-d H:i:s',$request->FechaCancelacion);
     
     
     
    if ($medicamento->MedicamentoActivo==1)
    {  
        
         $historialMedicamento->Estado="Cancelado";
         $historialMedicamento->save();
         

            $fechaSiguiente = $fecha->copy()->addHours((int)$medicamentoHorario->IntervaloHoras)->addMinutes((int)$medicamentoHorario->IntervaloMinutos);

            $siguienteDosis = $fechaSiguiente->format('Y-m-d H:i:s');

           $nuevoRegistro= $medicamentoHorario->historialAdministracion()->create([
                'FechaProgramada'=>$siguienteDosis,
                'HoraAdministracion'=>null,
                'Estado'=>'No Administrado',
                'Administro'=>null,
                'IdFamiliar'=>$request->IdFamiliar,
                'IdCuidador'=>$paciente->IdCuidador,
                'NombreM'=>$medicamento->NombreM,
                'NombreP'=>$paciente->Nombre,
                'Dosis'=>$medicamentoHorario->Dosis,
                'UnidadDosis'=>$medicamentoHorario->UnidaDosis,
                'Notas'=>$medicamentoHorario->Notas,
            ]);
            DB::commit();

            return response()->json(["message"=>"Administracion del medicamento cancelada,se ha generado el siguiente recordatorio de la dosis",
        "FechaSiguienteDosis"=>$nuevoRegistro->FechaProgramada],200);
    }
    else {
         $historialMedicamento->Estado="Cancelado";
         $historialMedicamento->save();
         DB::commit();
      return response()->json([
        "message" => "Administracion del medicamento cancelada, debido a que el medicamento no está activo no se generará la siguiente dosis",
        "FechaSiguienteDosis"=>null
    ], 200);
    }

    
    }catch(Exception $e){
     DB::rollBack();
     return response()->json(["message"=>$e->getMessage()],500);
    }
    
}

public function obtenerhistorialAdministracion(Request $request)
{
try{
        $request->validate([
            'IdFamiliar'=>['Required'],
            'TokenAcceso'=>['Required'],
        ]);
    }catch(\Illuminate\Validation\ValidationException $e)
    {
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
            $recordatorios=$familiar->historial()->where("Estado","!=","No Administrado")->orderBy("FechaProgramada","desc")->get();
            $recordatoriosConteo=$familiar->historial()->get();
            $recordatoriosCancelados=$recordatoriosConteo->where("Estado","=","Cancelado")->count();
            $recordatoriosNoAdministrados=$recordatoriosConteo->where("Estado","=","No Administrado")->count();
            $recordatoriosAdministrados=$recordatoriosConteo->where("Estado","=","Administrado")->count();
            $recordatoriosProximos=[];

            foreach($recordatorios as $recordatorio)
            {
                $Cuidador=$familiar->cuidadores()->where('IdCuidador',$recordatorio->IdCuidador)->first();
                $recordatoriosProximos[]=[
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
                    "NombreCuidador"=>$Cuidador?$Cuidador->Nombre:null
                ];
            }

            if ($recordatoriosProximos==[])
            {
                return response()->json(['message'=>'Aun no tiene registros en el historial de medicacion'],204);
            }
            else {
                return response()->json(['Recordatorios'=>$recordatoriosProximos,
            "RecordatoriosCancelados"=>$recordatoriosCancelados,
            "RecordatoriosNoAdministrados"=>$recordatoriosNoAdministrados,
            "RecordatoriosAdministrados"=>$recordatoriosAdministrados],200);
            }
}
    
}
