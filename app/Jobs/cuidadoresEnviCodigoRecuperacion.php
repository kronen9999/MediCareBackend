<?php

namespace App\Jobs;


use App\Mail\cuidadoresEnvioCodigoVerifiacion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
class cuidadoresEnviCodigoRecuperacion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $correo;
    public $codigo;

    public $tries = 5;
    public $timeout = 300;
    /**
     * Create a new job instance.
     */
    public function __construct($correo, $codigo)
    {
        $this->correo = $correo;
        $this->codigo = $codigo;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->correo)->send(new cuidadoresEnvioCodigoVerifiacion($this->correo, $this->codigo));
    }
}
