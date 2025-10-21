<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class informacioncontactopacientes extends Model
{
    protected $table = 'informacioncontactopacientes';

    protected $primaryKey = null;

    public $incrementing = false;

    public $fillable = [
        'IdPaciente',
        'Direccion',
        'Telefono1',
        'Telefono2'
    ];
    public $timestamps = false;
    
}
