<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
use App\Mail\cuidadoresNotificarFamiliarRecuperacion as CuidadoresNFR;


class cuidadoresNotificarFamiliarRecuperacion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

     public $usuario;
     public $correo;

     public $fechaHoraActual;

     public $tries = 5;
        public $timeout = 120;
    /**
     * Create a new job instance.
     */
    public function __construct($usuario, $correo, $fechaHoraActual)
    {
        $this->usuario = $usuario;
        $this->correo = $correo;
        $this->fechaHoraActual = $fechaHoraActual;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->correo)->send(new CuidadoresNFR($this->correo, $this->usuario, $this->fechaHoraActual));
    }
}
