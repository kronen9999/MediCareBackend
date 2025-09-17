<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class medicamentos extends Model
{
    protected $table = 'medicamentos';
    protected $primaryKey = 'IdMedicamento';
    public $timestamps = false;

    public function NombreM():Attribute{
       return  Attribute::make(
            set: fn($value) => strtoupper($value),
            get: fn($value) => strtoupper($value)
        );
    }

    public function DescripcionM():Attribute{
        return  Attribute::make(
             set: fn($value) => ucfirst(strtolower($value)),
             get: fn($value) => ucfirst(strtolower($value))
         );
     }

    public function TipoMedicamento():Attribute{
        return  Attribute::make(
             set: fn($value) => ucfirst(strtolower($value)),
             get: fn($value) => ucfirst(strtolower($value))
         );
     }

     public function paciente(){
        return $this->belongsTo(pacientes::class, 'IdPaciente', 'IdPaciente');
     }

     public function horariosMedicamentos(){
        return $this->hasMany(horariosMedicamentos::class, 'IdMedicamento', 'IdMedicamento');
     }
}
