<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class historialAdministracion extends Model
{
    protected $table = 'historialadministracion';
    protected $primaryKey = 'idhistorial';
    public $timestamps = false;
   
    public $fillable = [
        'FechaProgramada',
        'HoraAdministracion',
        'Estado',
        'Administro',
        'NombreM',
        'NombreP',
        'Dosis',
        'UnidadDosis',
        'Notas',
        'IdFamiliar',
        'IdCuidador',
    ];



    public function Cuidador()
    {
        return $this->belongsTo(cuidadores::class, 'IdCuidador');
    }

    public function Horario()
    {
        return $this->belongsTo(horariosMedicamentos::class, 'IdHorario');
    }
    
    public function familiar()
    {
        return $this->belongsTo(familiares::class,'IdFamiliar');
    }
}
