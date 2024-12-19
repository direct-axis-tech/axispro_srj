<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Accounting\JournalTransaction;
use App\Models\Hr\Employee;
use App\Models\Hr\Payroll;
use App\Models\Hr\Payslip;
use App\Models\SentHistory;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PayslipController extends Controller
{
    /**
     * Get the builder instance for querying payslips
     *
     * @param array $filters
     * @return \Illuminate\Database\Query\Builder
     */
    public function builder($filters)
    {
        $query = DB::query()
            ->select(
                'pslip.*',
                'emp.emp_ref',
                'emp.machine_id',
                'emp.name',
                'emp.date_of_join',
                'emp.emirates_id',
                'emp.personal_id_no',
                'dep.name as department',
                'desig.name as designation',
                DB::raw("IF(pslip.mode_of_pay = 'B', 'Bank', 'Cash') as mode_of_payment"),
                'country.name as country',
                'bank.name as bank_name',
                'bank.routing_no',
                'wCom.name as working_company',
                'vCom.name as visa_company'
            )
            ->from('0_payslips as pslip')
            ->leftJoin('0_employees as emp', 'emp.id', 'pslip.employee_id')
            ->leftJoin('0_departments as dep', 'dep.id', 'pslip.department_id')
            ->leftJoin('0_designations as desig', 'desig.id', 'pslip.designation_id')
            ->leftJoin('0_countries as country', 'country.code', 'emp.nationality')
            ->leftJoin('0_banks as bank', 'bank.id', 'pslip.bank_id')
            ->leftJoin('0_companies as wCom', 'wCom.id', 'pslip.working_company_id')
            ->leftJoin('0_companies as vCom', 'vCom.id', 'pslip.visa_company_id');

        if (!empty($filters['payroll_id'])) {
            $query->where('pslip.payroll_id', $filters['payroll_id']);;
        }

        if (!empty($filters['department_id'])) {
            if (is_array($filters['department_id'])) {
                $filters['department_id'] = implode(",", $filters['department_id']);
            }

            $query->whereIn('pslip.department_id', explode(',', $filters['department_id']));;
        }

        if (!empty($filters['working_company_id'])) {
            if (is_array($filters['working_company_id'])) {
                $filters['working_company_id'] = implode(",", $filters['working_company_id']);
            }

            $query->whereIn('pslip.working_company_id', explode(',', $filters['working_company_id']));;
        }

        if (!empty($filters['employee_id'])) {
            if (is_array($filters['employee_id'])) {
                $filters['employee_id'] = implode(",", $filters['employee_id']);
            }

            $query->whereIn('pslip.employee_id', explode(',', $filters['employee_id']));;
        }

        if (isset($filters['is_processed'])) {
            $query->where('pslip.is_processed', (int)((bool)$filters['is_processed']));;
        }
        
        if (!empty($filters['payslip_id'])) {
            $query->where('pslip.id', $filters['payslip_id']);;
        }

        return $query;
    }

    /**
     * Render the payslip
     *
     * @param string $payrollId
     * @param string $employeeId
     * @return string
     */
    public function render($payrollId, $employeeId)
    {
        $payroll = Payroll::find($payrollId);
        $payslip = (array)$this->builder([
            "payroll_id" => $payrollId,
            "employee_id" => $employeeId
        ])->first();
        $payElements = getResultAsArray(app(PayslipElementController::class)->builder([
            "payslip_id" => $payslip['id'] ?? -1]
        ));

        $allowances = array_filter($payElements, function ($payElement) {
            return $payElement['type'] == 1;
        });
        $deductions = array_filter($payElements, function ($payElement) {
            return $payElement['type'] == -1;
        });
        $totalAllowances = array_sum(array_column($allowances, 'amount'));
        $totalDeductions = array_sum(array_column($deductions, 'amount'));

        $maxElementsCount = max(count($allowances), count($deductions));
        $allowances = array_pad($allowances, $maxElementsCount, []);
        $deductions = array_pad($deductions, $maxElementsCount, []);

        return view('hr.payslip', compact(
            'payroll',
            'payslip',
            'allowances',
            'totalAllowances',
            'deductions',
            'totalDeductions'
        ))->render();
    }

    /**
     * Handle the print request for payslip
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function print(Request $request, Payroll $payroll, Employee $employee)
    {
        $payslipPath = $this->generatePayslipPdf($payroll, $employee);
        
        return response()->file($payslipPath, [
            'Content-Disposition' => 'inline; filename="' . $payslipPath . '"',
        ]);
    }
    
    /**
     * generatePayslipPdf
     *
     * @param  mixed $payroll
     * @param  mixed $employee
     * @return string $filePath
     */
    public function generatePayslipPdf(Payroll $payroll, Employee $employee)
    {
        abort_unless($payroll->is_processed, 404);
        abort_unless(
            Payslip::query()
                ->where('payroll_id', $payroll->id)
                ->where('employee_id', $employee->id)
                ->exists(),
                404
        );

        $mpdf = app(\Mpdf\Mpdf::class);
        $mpdf->SetTitle('Employee Payslip');
        $mpdf->WriteHTML($this->render($payroll->id, $employee->id), \Mpdf\HTMLParserMode::HTML_BODY);

        $fileName = Str::orderedUuid()."_payslip_{$payroll->year}{$payroll->month}_{$employee->emp_ref}.pdf";
        $filePath = storage_path("/download/{$fileName}");

        $mpdf->Output($filePath, \Mpdf\Output\Destination::FILE);

        return $filePath;
    }

    /**
     * Send payslip emails to all employees for a payroll
     *
     * @param int $payrollId
     * @return \Illuminate\Http\JsonResponse
     */
    public static function sendPayslipEmail($payrollId, $employeeId = null)
    {
        if (empty(config('mail.driver')) || empty(config('mail.host')) || empty(config('mail.username')) || empty(config('mail.password'))) {
            \Log::error('Email configuration is missing. Skipping email-sending process.');
            return response()->json(['error' => 'Email configuration is missing.'], 500);
        }
    
        $payroll = Payroll::find($payrollId);
    
        if (!$payroll) {
            return response()->json(['error' => 'Invalid payroll ID.'], 404);
        }
    
        $payslips = Payslip::query()
            ->where('payroll_id', $payrollId)
            ->with(['employee']);
    
        if ($employeeId) {
            $payslips->where('employee_id', $employeeId);
        }
    
        $payslips->chunk(50, function ($chunk) use ($payroll) {
            foreach ($chunk as $payslip) {
                $employee = $payslip->employee;
    
                if (!$employee || empty($employee->email)) {
                    \Log::warning("Skipping payslip email for employee ID {$payslip->employee_id}: No email found.");
                    continue;
                }
    
                try {
                    $pdfPath = app(PayslipController::class)->generatePayslipPdf($payroll, $employee);
                    $payrollMonth = DateTime::createFromFormat('!m', $payroll->month)->format('F');
                    
                    $mailBody = "Dear {$employee->name},\n\n"
                        . "Please find your payslip for the month of {$payrollMonth}, {$payroll->year} attached.\n\n"
                        . "Regards,\nHR Department";
    
                    Mail::send([], [], function ($message) use ($employee, $mailBody, $pdfPath, $payrollMonth, $payroll) {
                        $message->to($employee->email)
                            ->subject('Payslip For The Month Of ' . $payrollMonth . ', ' . $payroll->year)
                            ->attach($pdfPath, [
                                'as' => 'Payslip.pdf',
                                'mime' => 'application/pdf',
                            ])
                            ->setBody($mailBody, 'text/plain');
                    });
    
                    SentHistory::create([
                        'trans_type' => JournalTransaction::PAYROLL,
                        'trans_no' => $payroll->id,
                        'trans_ref' => $payslip->id,
                        'content' => "Payslip of {$employee->name} for {$payrollMonth}, {$payroll->year}",
                        'sent_through' => NT_EMAIL,
                        'sent_to' => $employee->email,
                        'sent_at' => now(),
                        'resource_ref' => auth()->id(),
                    ]);
    
                    if (file_exists($pdfPath)) {
                        unlink($pdfPath);
                    }
                } catch (\Exception $e) {
                    \Log::error("Failed to send payslip email to employee ID {$payslip->employee_id}: " . $e->getMessage());
                }
            }
        });
    
        return response()->json(['message' => 'Payslip emails sent successfully.'], 200);
    }
}