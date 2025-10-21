<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\cuidadores;

class informacionContactoCuidador extends Model
{
   protected $table="informacioncontactocuidador";

    protected $primaryKey=null;

    public $incrementing=false;

   protected $fillable = [
       'IdCuidador',
       'Direccion',
       'Telefono1',
       'Telefono2'
   ];

   public $timestamps=false;

   public function cuidador()
   {
       return $this->belongsTo(cuidadores::class, 'IdCuidador', 'IdCuidador');
   }

}
