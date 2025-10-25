<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class horariosMedicamentos extends Model
{
    protected $table = 'horariosmedicamentos';

    protected $primaryKey="IdHorario";

    public $timestamps = false;

    public $fillable = [
        'HoraPrimeraDosis',
        'IntervaloHoras',
        'Dosis',
        'UnidaDosis',
        'Notas'
    ];

    public function Notas():Attribute{
        return Attribute::make(
           set: fn($value) => ucfirst(strtolower($value)),
           get: fn($value) => ucfirst(strtolower($value)),
        );
    }

    public function medicamento(){
        return $this->belongsTo(medicamentos::class, 'IdMedicamento', 'IdMedicamento');
    }

    public function historialAdministracion()
    {
        return $this->hasMany(historialAdministracion::class, 'IdHorario', 'IdHorario');
    }

}
