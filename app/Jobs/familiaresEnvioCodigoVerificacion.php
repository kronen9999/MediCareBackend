<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Mail\familiaresEnvioCodigoMail;
use Illuminate\Support\Facades\Mail;


class familiaresEnvioCodigoVerificacion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $correoE;
    public $codigoVerificacion;
    /**
     * Create a new job instance.
     */
    public function __construct($correoE,$codigoVerificacion)
    {
        $this->correoE = $correoE;
        $this->codigoVerificacion = $codigoVerificacion;
    }   
  

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->correoE)->send(new familiaresEnvioCodigoMail($this->correoE, $this->codigoVerificacion));
    }
}
