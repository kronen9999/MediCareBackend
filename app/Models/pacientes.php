<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class pacientes extends Model
{
   protected $table = 'pacientes';
    protected $primaryKey = 'IdPaciente';

    public $fillable = [
        'Nombre',
        'ApellidoP',
        'ApellidoM',
    ];

    public $timestamps = false;

    public function Nombre():Attribute
    {
        return new Attribute(
           set: fn($value) => ucfirst(strtolower($value)),
           get: fn($value) => ucfirst(strtolower($value)),
        );
    }
     public function ApellidoP():Attribute
    {
        return new Attribute(
           set: fn($value) => ucfirst(strtolower($value)),
           get: fn($value) => ucfirst(strtolower($value)),
        );
    }
     public function ApellidoM():Attribute
    {
        return new Attribute(
           set: fn($value) => ucfirst(strtolower($value)),
           get: fn($value) => ucfirst(strtolower($value)),
        );
    }

    public function familiares()
    {
        return $this->belongsTo(familiares::class, 'IdFamiliar', 'IdFamiliar');
    }
     public function cuidadores()
    {
        return $this->belongsTo(cuidadores::class, 'IdCuidador', 'IdCuidador');
    }

    public function medicamentos()
    {
        return $this->hasMany(medicamentos::class, 'IdPaciente', 'IdPaciente');
    }
    public function informacionContactoPaciente()
    {
        return $this->hasOne(informacioncontactopacientes::class, 'IdPaciente', 'IdPaciente');
    }

    

}
