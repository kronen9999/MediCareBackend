<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class historialAdministracion extends Model
{
    protected $table = 'historialadministracion';
    protected $primaryKey = 'idhistorial';
    public $timestamps = false;
   

    public function Familiar()
    {
        return $this->belongsTo(familiares::class, 'IdFamiliar');
    }

    public function Medicamento()
    {
        return $this->belongsTo(medicamentos::class, 'IdMedicamento');
    }

    public function Paciente()
    {
        return $this->belongsTo(pacientes::class, 'IdPaciente');
    }

    public function Cuidador()
    {
        return $this->belongsTo(cuidadores::class, 'IdCuidador');
    }

    public function Horario()
    {
        return $this->belongsTo(horariosMedicamentos::class, 'IdHorario');
    }
    
}
