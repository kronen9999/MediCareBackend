<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class informacionContactoFamiliar extends Model
{
    protected $table = 'informacioncontactofamiliar';
   
    public $timestamps = false; 

    

 
    public function familiar()
    {
        return $this->belongsTo(familiares::class, 'IdFamiliar', 'IdFamiliar');
    }

}
