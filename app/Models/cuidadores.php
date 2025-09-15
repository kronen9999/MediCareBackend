<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class cuidadores extends Model
{
    protected $table = 'cuidadores';
    protected $primaryKey = 'IdCuidador';
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


    // Definir la relación con el modelo familiares
    public function familiar()
    {
        return $this->belongsTo(familiares::class, 'IdFamiliar', 'IdFamiliar');
    }
}
