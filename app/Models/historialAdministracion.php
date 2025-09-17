<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class historialAdministracion extends Model
{
    protected $table = 'historialadministracion';
    protected $primaryKey = 'idhistorial';
    public $timestamps = false;
   



    public function Cuidador()
    {
        return $this->belongsTo(cuidadores::class, 'IdCuidador');
    }

    public function Horario()
    {
        return $this->belongsTo(horariosMedicamentos::class, 'IdHorario');
    }
    
}
