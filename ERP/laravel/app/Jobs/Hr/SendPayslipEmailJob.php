<?php

namespace App\Jobs\Hr;

use App\Http\Controllers\Hr\PayslipController;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendPayslipEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * payrollId
     *
     * @var mixed
     */
    protected $payrollId;
    
    /**
     * employeeId
     *
     * @var mixed
     */
    protected $employeeId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($payrollId, $employeeId = null)
    {
        $this->payrollId  = $payrollId;
        $this->employeeId = $employeeId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        PayslipController::sendPayslipEmail($this->payrollId, $this->employeeId);
    }
}
