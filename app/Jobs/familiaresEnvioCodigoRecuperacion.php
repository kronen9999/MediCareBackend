<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Mail\FamiliaresEnvioCodigoRecuperacion as famECR;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;

class familiaresEnvioCodigoRecuperacion implements ShouldQueue
{
       use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */

    public $tries = 5;
    public $timeout = 30;
        public $correoE;
    public $codigoRecuperacion;
    public function __construct($correoE,$codigoRecuperacion)
    {
        $this->correoE = $correoE;
        $this->codigoRecuperacion = $codigoRecuperacion;
    }
    

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->correoE)->send(new famECR($this->correoE, $this->codigoRecuperacion));
    }
}
