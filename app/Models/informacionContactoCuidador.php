<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\cuidadores;

class informacionContactoCuidador extends Model
{
   protected $table="informacioncontactocuidador";

   public $timestamps=false;

   public function cuidador()
   {
       return $this->belongsTo(cuidadores::class, 'IdCuidador', 'IdCuidador');
   }

}
