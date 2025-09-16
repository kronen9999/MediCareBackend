<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class familiares extends Model
{
    protected $table = 'familiares';
    protected $primaryKey = 'IdFamiliar';
    public $timestamps = false;

    public function Nombre():Attribute{
        return Attribute::make(
            get: fn($value) => ucfirst(strtolower($value)),
            set: fn($value) => ucfirst(strtolower($value)),
        );
    }
     public function ApellidoM():Attribute{
        return Attribute::make(
            get: fn($value) => ucfirst(strtolower($value)),
            set: fn($value) => ucfirst(strtolower($value)),
        );
    }
     public function ApellidoP():Attribute{
        return Attribute::make(
            get: fn($value) => ucfirst(strtolower($value)),
            set: fn($value) => ucfirst(strtolower($value)),
        );
    }

    public function CorreoE():Attribute{
        return Attribute::make(
            get: fn($value) => strtolower($value),
            set: fn($value) => strtolower($value),
        );
    }

    public function Contrasena():Attribute{
        return Attribute::make(
            set: fn($value) => bcrypt($value),);
    }

    // Definir la relación con el modelo cuidadores
    public function cuidadores()
    {
        return $this->hasMany(cuidadores::class, 'IdFamiliar', 'IdFamiliar');
    }

    public function informacionContactoFamiliar()
    {
        return $this->hasOne(informacionContactoFamiliar::class, 'IdFamiliar', 'IdFamiliar');
    }

    public function pacientes()
    {
        return $this->hasMany(pacientes::class, 'IdFamiliar', 'IdFamiliar');
    }

    

}
