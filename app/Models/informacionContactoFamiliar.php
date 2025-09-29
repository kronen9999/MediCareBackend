<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class informacionContactoFamiliar extends Model
{
    protected $table = 'informacioncontactofamiliar';
   
    public $timestamps = false; 
    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'IdFamiliar',
        'Direccion',
        'Telefono1',
        'Telefono2',
    ];

    

 
    public function familiar()
    {
        return $this->belongsTo(familiares::class, 'IdFamiliar', 'IdFamiliar');
    }

}
