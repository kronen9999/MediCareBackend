<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class cuidadores extends Model
{
    protected $table = 'cuidadores';
    protected $primaryKey = 'IdCuidador';
    public $timestamps = false;


    // Definir la relación con el modelo familiares
    public function familiar()
    {
        return $this->belongsTo(familiares::class, 'IdFamiliar', 'IdFamiliar');
    }
}
