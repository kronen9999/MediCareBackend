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
    /**
     * Create a new job instance.
     */
    public function __construct($usuario, $correo)
    {
        $this->usuario = $usuario;
        $this->correo = $correo;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->correo)->send(new CuidadoresNFR($this->correo, $this->usuario));
    }
}
