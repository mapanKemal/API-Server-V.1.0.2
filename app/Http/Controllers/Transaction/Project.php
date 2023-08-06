<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Controller;
use App\Models\Approvals\Approval;
use App\Models\Approvals\Detail_Approval;
use App\Models\Master\Company;
use App\Models\Master\Departement;
use App\Models\Master\Employee;
use App\Models\Transaction\DtProject;
use App\Models\Transaction\Project as TransactionProject;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;

class Project extends Controller
{
    protected $advance_transTyId = 2, $reimburse_transTyId = 9, $general_transTyId = 16;
    protected $maxCloseProject = 8;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return response(TransactionProject::all());
    }
    public function index_empTransProgress(Request $request)
    {
        $compId = [];
        $deptId = [];

        foreach ($request->empStructure as $key => $valStructure) {
            array_push($compId, $valStructure['COMP_ID']);
            array_push($deptId, $valStructure['DEPT_ID']);
        }

        $result = [];
        $transactionData = TransactionProject::select(
            "tr_project_request.PRJ_ID",
            "tr_project_request.UUID",
            "tr_project_request.PRJ_NUMBER",
            "tr_project_request.PRJ_SUBJECT",
            "tr_project_request.PRJ_NOTES",
            "tr_project_request.PRJ_TOTAL_AMOUNT_REQUEST",
            "tr_project_request.PRJ_TOTAL_AMOUNT_USED",
            "tr_project_request.STATUS",
            "tr_project_request.APPROVAL_ID",
            // "tr_project_request.PRJ_ATTTACHMENT
            "ms_company.COMP_CODE",
            "ms_company.COMP_NAME",
            "ms_departement.DEPT_CODE",
            "ms_departement.DEPT_NAME",
        )
            ->join('ms_company', 'tr_project_request.COMP_ID', '=', 'ms_company.COMP_ID')
            ->join('ms_departement', 'tr_project_request.DEPT_ID', '=', 'ms_departement.DEPT_ID')
            ->where([
                ['tr_project_request.EMPL_ID', $request->employeeId]
            ])
            ->whereNotNull('tr_project_request.APPROVAL_ID')
            ->whereIn('tr_project_request.COMP_ID', $compId)
            ->whereIn('tr_project_request.DEPT_ID', $deptId)
            ->get();

        foreach ($transactionData as $keyTrd => $valTrd) {
            $timeLine = [];
            /* Set Status */
            $approvalStatus = Approval::select(
                "dt_approval.DT_APPR_ID",
                "dt_approval.STATUS",
                "ms_approval_code.APPROVAL_CODE_ID",
                "ms_approval_code.APPROVAL_CODE_DESC",
                "ms_approval_code.SYS_APPROVAL_VARIANT",
                "ms_employee.EMPL_FIRSTNAME",
                "ms_employee.EMPL_LASTNAME",
            )
                ->join('dt_approval', 'tr_approval.APPROVAL_ID', '=', 'dt_approval.APPROVAL_ID')
                ->join('ms_approval_code', 'dt_approval.APPROVAL_CODE_ID', '=', 'ms_approval_code.APPROVAL_CODE_ID')
                ->join('ms_employee', 'dt_approval.EMPL_ID', '=', 'ms_employee.EMPL_ID')
                ->where([
                    ['tr_approval.APPROVAL_ID', $valTrd->APPROVAL_ID]
                ])
                ->whereRaw('dt_approval.DT_APPR_NUMBER = (
                    select max(DAP.DT_APPR_NUMBER) 
                    from tr_approval TAP 
                    join dt_approval DAP on DAP.APPROVAL_ID = TAP.APPROVAL_ID 
                    where DAP.APPROVAL_ID = ' . $valTrd->APPROVAL_ID . ' 
                        and DAP.STATUS != 0 
                    )')
                ->get();
            foreach ($approvalStatus as $key => $valStatus) {
                $valTrd->APPR_STATUS = $valStatus->STATUS;
                $valTrd->DT_APPR_ID = $valStatus->DT_APPR_ID;
                $valTrd->APPROVAL_CODE_ID = $valStatus->APPROVAL_CODE_ID;
                $valTrd->APPROVAL_COLOR = $valStatus->SYS_APPROVAL_VARIANT;
                $valTrd->APPROVAL_CODE_DESC = $valStatus->APPROVAL_CODE_DESC;
                $valTrd->FIRSTNAME = $valStatus->EMPL_FIRSTNAME;
                $valTrd->LASTNAME = $valStatus->EMPL_LASTNAME;
            }
            /* Create Timeline */
            $getTimeline = Approval::select(
                "dt_approval.DT_APPR_ID",
                "dt_approval.USER_ID",
                "dt_approval.EMPL_ID",
                "dt_approval.APPROVAL_CODE_ID",
                "dt_approval.UUID as APPROVAL_UUID",
                "dt_approval.DT_APPR_NUMBER",
                "dt_approval.DT_APPR_REQ_DATE",
                "dt_approval.DT_APPR_DATE",
                "dt_approval.DT_APPR_DESCRIPTION",
                "dt_approval.DT_APPR_DEACTIVE",
                "dt_approval.STATUS",
                "dt_approval.LOG_ACTIVITY",
                "ms_approval_code.APPROVAL_CODE_ID",
                "ms_approval_code.APPROVAL_CODE_DESC",
                "ms_approval_code.SYS_APPROVAL_VARIANT",
                "ms_employee.EMPL_FIRSTNAME",
                "ms_employee.EMPL_LASTNAME",
            )
                ->join('dt_approval', 'tr_approval.APPROVAL_ID', '=', 'dt_approval.APPROVAL_ID')
                ->join('ms_approval_code', 'dt_approval.APPROVAL_CODE_ID', '=', 'ms_approval_code.APPROVAL_CODE_ID')
                ->join('ms_employee', 'dt_approval.EMPL_ID', '=', 'ms_employee.EMPL_ID')
                ->where([
                    ['tr_approval.APPROVAL_ID', $valTrd->APPROVAL_ID],
                    ['dt_approval.APPROVAL_CODE_ID', '!=', 1]
                ])
                ->get();
            $row = 0;
            foreach ($getTimeline as $keyApprTimeline => $valApprTimeline) {
                $valApprTimeline['msStatusColor'] = $valApprTimeline->SYS_APPROVAL_VARIANT == "danger" ? "error" : $valApprTimeline->SYS_APPROVAL_VARIANT;
                $valApprTimeline['msContent'] = $valApprTimeline->EMPL_FIRSTNAME . ' ' . $valApprTimeline->EMPL_LASTNAME;
                $time = [];
                foreach ($valApprTimeline->LOG_ACTIVITY as $keyLgAct => $valActivity) {
                    $res = "[" . $valActivity['option']['APPROVAL_STATUS_DESC'] . "] " . Carbon::parse($valActivity['option']['DT_APPR_DATE'] == null ? $valActivity['option']['DT_APPR_REQ_DATE'] : $valActivity['option']['DT_APPR_DATE'])->translatedFormat('d F Y | H:i');
                    array_push($time, $res);
                }
                $valApprTimeline['msTime'] = $time;
                array_push($timeLine, $valApprTimeline);
                // echo json_encode($valApprTimeline);
                $row = $row + 1;
            }
            $valTrd->PRJ_TIMELINE = $timeLine;
            array_push($result, $valTrd);
        }

        // return response($transactionData);
        return response($result);
        // print_r(DB::getQueryLog());
        // $res = DB::raw(vsprintf($rawSql, $bindings));
        // dd($rawSql, $bindings);
        // $prjTransaction = [];
        // $result_2 = [];
        // foreach ($request->empStructure as $keyEmpStructure => $valEmpStructure) {
        //     $data = TransactionProject::select(
        //         "tr_project_request.PRJ_ID",
        //         "tr_project_request.UUID",
        //         "tr_project_request.PRJ_NUMBER",
        //         "tr_project_request.PRJ_SUBJECT",
        //         "tr_project_request.PRJ_NOTES",
        //         "tr_project_request.PRJ_TOTAL_AMOUNT_REQUEST",
        //         "tr_project_request.PRJ_TOTAL_AMOUNT_USED",
        //         "tr_project_request.STATUS",
        //         "tr_project_request.APPROVAL_ID",
        //         // "tr_project_request.PRJ_ATTTACHMENT",
        //         "ms_company.COMP_CODE",
        //         "ms_company.COMP_NAME",
        //         "ms_departement.DEPT_CODE",
        //         "ms_departement.DEPT_NAME",
        //         "DAPT.STATUS as APPR_STATUS",
        //         "DAPT.DT_APPR_ID",
        //         "DAPT.APPROVAL_CODE_ID",
        //         "ms_approval_code.SYS_APPROVAL_VARIANT as APPROVAL_COLOR",
        //         "ms_approval_code.APPROVAL_CODE_DESC",
        //         "ms_employee.EMPL_FIRSTNAME as FIRSTNAME",
        //         "ms_employee.EMPL_LASTNAME as LASTNAME",
        //     )
        //         ->leftJoin('ms_company', 'tr_project_request.COMP_ID', '=', 'ms_company.COMP_ID')
        //         ->leftJoin('ms_departement', 'tr_project_request.DEPT_ID', '=', 'ms_departement.DEPT_ID')
        //         ->leftJoin('dt_approval as DAPT', 'tr_project_request.APPROVAL_ID', '=', 'DAPT.APPROVAL_ID')
        //         ->leftJoin('ms_approval_code', 'DAPT.APPROVAL_CODE_ID', '=', 'ms_approval_code.APPROVAL_CODE_ID')
        //         ->leftJoin('ms_employee', 'DAPT.EMPL_ID', '=', 'ms_employee.EMPL_ID')
        //         ->where([
        //             // ['tr_project_request.STATUS', 1],
        //             ['tr_project_request.EMPL_ID', $request->employeeId],
        //             ['tr_project_request.COMP_ID', $valEmpStructure['COMP_ID']],
        //             ['tr_project_request.DEPT_ID', $valEmpStructure['DEPT_ID']],
        //             ['DAPT.STATUS', '!=', 0],
        //         ])
        //         ->whereRaw('DAPT.DT_APPR_NUMBER = (
        //             select max(DAP.DT_APPR_NUMBER)
        //             from tr_approval TAP
        //             join dt_approval DAP on DAP.APPROVAL_ID = TAP.APPROVAL_ID
        //             where DAP.APPROVAL_ID = DAPT.APPROVAL_ID
        //             and DAP.STATUS != 0 
        //             and TAP.COMP_ID=' . $valEmpStructure['COMP_ID'] . ' 
        //             and TAP.DEPT_ID=' . $valEmpStructure['DEPT_ID'] . ' 
        //         )')
        //         ->get();
        //     foreach ($data as $key => $valData) {
        //         $timelineData = [];
        //         $timeline = Approval::select(
        //             "tr_approval.APPROVAL_ID",
        //             "tr_approval.STATUS",
        //             "tr_approval.CREATED_AT",
        //             "dt_approval.DT_APPR_ID",
        //             "dt_approval.USER_ID",
        //             "dt_approval.EMPL_ID",
        //             "dt_approval.DT_APPR_REQ_DATE",
        //             "dt_approval.DT_APPR_DATE",
        //             "dt_approval.DT_APPR_DESCRIPTION",
        //             "dt_approval.LOG_ACTIVITY",
        //             "ms_approval_code.APPROVAL_CODE_ID",
        //             "ms_approval_code.APPROVAL_CODE_DESC",
        //             "ms_approval_code.SYS_APPROVAL_VARIANT",
        //         )
        //             ->join('dt_approval', 'tr_approval.APPROVAL_ID', '=', 'dt_approval.APPROVAL_ID')
        //             ->join('ms_approval_code', 'dt_approval.APPROVAL_CODE_ID', '=', 'ms_approval_code.APPROVAL_CODE_ID')
        //             ->where([
        //                 ['tr_approval.APPROVAL_ID', $valData->APPROVAL_ID],
        //                 ['dt_approval.APPROVAL_CODE_ID', '!=', 1]
        //             ])
        //             ->get();
        //         $row = 0;
        //         foreach ($timeline as $keyTimeline => $valTimeline) {
        //             $empl = Employee::where([['EMPL_ID', $valTimeline->EMPL_ID]])->firstOrFail();
        //             $valTimeline['msStatusColor'] = $valTimeline->SYS_APPROVAL_VARIANT == "danger" ? "error" : $valTimeline->SYS_APPROVAL_VARIANT;
        //             $valTimeline['msContent'] = $empl->EMPL_FIRSTNAME . ' ' . $empl->EMPL_LASTNAME;
        //             $time = [];
        //             foreach ($valTimeline->LOG_ACTIVITY as $keyLgAct => $valActivity) {
        //                 $res = "[" . $valActivity['option']['APPROVAL_STATUS_DESC'] . "] " . Carbon::parse($valActivity['option']['DT_APPR_DATE'] == null ? $valActivity['option']['DT_APPR_REQ_DATE'] : $valActivity['option']['DT_APPR_DATE'])->translatedFormat('d F Y H:i');
        //                 array_push($time, $res);
        //             }
        //             $valTimeline['msTime'] = $time;

        //             array_push($timelineData, $valTimeline);
        //             $row = $row + 1;
        //         }
        //         $valData->PRJ_TIMELINE = $timelineData;
        //     }
        //     array_push($prjTransaction, $data);
        // }
        // foreach ($prjTransaction as $key => $val_1) {
        //     foreach ($val_1 as $key => $val_2) {
        //         array_push($result_2, $val_2);
        //     }
        // }
    }
    public function index_empTransProgress2(Request $request)
    {

        $prjTransaction = [];
        $result_2 = [];
        foreach ($request->empStructure as $keyEmpStructure => $valEmpStructure) {
            $data = TransactionProject::select(
                "tr_project_request.PRJ_ID",
                "tr_project_request.UUID",
                "tr_project_request.PRJ_NUMBER",
                "tr_project_request.PRJ_SUBJECT",
                "tr_project_request.PRJ_NOTES",
                "tr_project_request.PRJ_TOTAL_AMOUNT_REQUEST",
                "tr_project_request.PRJ_TOTAL_AMOUNT_USED",
                "tr_project_request.STATUS",
                "tr_project_request.APPROVAL_ID",
                // "tr_project_request.PRJ_ATTTACHMENT",
                "ms_company.COMP_CODE",
                "ms_company.COMP_NAME",
                "ms_departement.DEPT_CODE",
                "ms_departement.DEPT_NAME",
                "DAPT.STATUS as APPR_STATUS",
                "DAPT.DT_APPR_ID",
                "DAPT.APPROVAL_CODE_ID",
                "ms_approval_code.SYS_APPROVAL_VARIANT as APPROVAL_COLOR",
                "ms_approval_code.APPROVAL_CODE_DESC",
                "ms_employee.EMPL_FIRSTNAME as FIRSTNAME",
                "ms_employee.EMPL_LASTNAME as LASTNAME",
            )
                ->leftJoin('ms_company', 'tr_project_request.COMP_ID', '=', 'ms_company.COMP_ID')
                ->leftJoin('ms_departement', 'tr_project_request.DEPT_ID', '=', 'ms_departement.DEPT_ID')
                ->leftJoin('dt_approval as DAPT', 'tr_project_request.APPROVAL_ID', '=', 'DAPT.APPROVAL_ID')
                ->leftJoin('ms_approval_code', 'DAPT.APPROVAL_CODE_ID', '=', 'ms_approval_code.APPROVAL_CODE_ID')
                ->leftJoin('ms_employee', 'DAPT.EMPL_ID', '=', 'ms_employee.EMPL_ID')
                ->where([
                    // ['tr_project_request.STATUS', 1],
                    ['tr_project_request.EMPL_ID', $request->employeeId],
                    ['tr_project_request.COMP_ID', $valEmpStructure['COMP_ID']],
                    ['tr_project_request.DEPT_ID', $valEmpStructure['DEPT_ID']],
                    ['DAPT.STATUS', '!=', 0],
                ])
                ->whereRaw('DAPT.DT_APPR_NUMBER = (
                    select max(DAP.DT_APPR_NUMBER)
                    from tr_approval TAP
                    join dt_approval DAP on DAP.APPROVAL_ID = TAP.APPROVAL_ID
                    where DAP.APPROVAL_ID = DAPT.APPROVAL_ID
                    and DAP.STATUS != 0 
                    and TAP.COMP_ID=' . $valEmpStructure['COMP_ID'] . ' 
                    and TAP.DEPT_ID=' . $valEmpStructure['DEPT_ID'] . ' 
                )')
                ->get();
            foreach ($data as $key => $valData) {
                $timelineData = [];
                $timeline = Approval::select(
                    "tr_approval.APPROVAL_ID",
                    "tr_approval.STATUS",
                    "tr_approval.CREATED_AT",
                    "dt_approval.DT_APPR_ID",
                    "dt_approval.USER_ID",
                    "dt_approval.EMPL_ID",
                    "dt_approval.DT_APPR_REQ_DATE",
                    "dt_approval.DT_APPR_DATE",
                    "dt_approval.DT_APPR_DESCRIPTION",
                    "dt_approval.LOG_ACTIVITY",
                    "ms_approval_code.APPROVAL_CODE_ID",
                    "ms_approval_code.APPROVAL_CODE_DESC",
                    "ms_approval_code.SYS_APPROVAL_VARIANT",
                )
                    ->join('dt_approval', 'tr_approval.APPROVAL_ID', '=', 'dt_approval.APPROVAL_ID')
                    ->join('ms_approval_code', 'dt_approval.APPROVAL_CODE_ID', '=', 'ms_approval_code.APPROVAL_CODE_ID')
                    ->where([
                        ['tr_approval.APPROVAL_ID', $valData->APPROVAL_ID],
                        ['dt_approval.APPROVAL_CODE_ID', '!=', 1]
                    ])
                    ->get();
                $row = 0;
                foreach ($timeline as $keyTimeline => $valTimeline) {
                    $empl = Employee::where([['EMPL_ID', $valTimeline->EMPL_ID]])->firstOrFail();
                    $valTimeline['msStatusColor'] = $valTimeline->SYS_APPROVAL_VARIANT == "danger" ? "error" : $valTimeline->SYS_APPROVAL_VARIANT;
                    $valTimeline['msContent'] = $empl->EMPL_FIRSTNAME . ' ' . $empl->EMPL_LASTNAME;
                    $time = [];
                    foreach ($valTimeline->LOG_ACTIVITY as $keyLgAct => $valActivity) {
                        $res = "[" . $valActivity['option']['APPROVAL_STATUS_DESC'] . "] " . Carbon::parse($valActivity['option']['DT_APPR_DATE'] == null ? $valActivity['option']['DT_APPR_REQ_DATE'] : $valActivity['option']['DT_APPR_DATE'])->translatedFormat('d F Y H:i');
                        array_push($time, $res);
                    }
                    $valTimeline['msTime'] = $time;

                    array_push($timelineData, $valTimeline);
                    $row = $row + 1;
                }
                $valData->PRJ_TIMELINE = $timelineData;
            }
            array_push($prjTransaction, $data);
        }
        foreach ($prjTransaction as $key => $val_1) {
            foreach ($val_1 as $key => $val_2) {
                array_push($result_2, $val_2);
            }
        }
        return response($result_2);
    }

    public function index_newTransaction(Request $request)
    {
        $result = TransactionProject::select(
            "tr_project_request.*",
            "ms_company.COMP_CODE",
            "ms_company.COMP_NAME",
            "ms_departement.DEPT_CODE",
            "ms_departement.DEPT_NAME",
        )
            ->join('fr_employee_position', 'tr_project_request.EMPL_ID', '=', 'fr_employee_position.EMPL_ID')
            ->leftJoin('ms_company', 'fr_employee_position.COMP_ID', '=', 'ms_company.COMP_ID')
            ->leftJoin('ms_departement', 'fr_employee_position.DEPT_ID', '=', 'ms_departement.DEPT_ID')
            ->where([
                ['tr_project_request.STATUS', 0],
                ['tr_project_request.EMPL_ID', $request->employeeId],
            ])
            ->get();

        return response($result);
    }
    public function index_transByHeader(string $uuid)
    {
        //
        return response(TransactionProject::select(
            "tr_project_request.*",
            "ms_company.COMP_CODE",
            "ms_company.COMP_NAME",
            "ms_departement.DEPT_CODE",
            "ms_departement.DEPT_NAME",
        )
            ->join('ms_company', 'tr_project_request.COMP_ID', '=', 'ms_company.COMP_ID')
            ->join('ms_departement', 'tr_project_request.DEPT_ID', '=', 'ms_departement.DEPT_ID')
            ->where('UUID', $uuid)
            ->firstOrFail());
    }

    public function index_detailByHeader(string $uuid)
    {
        // secara operational bisa enak
        $result = [];

        /* Set Update Project Amount */
        $detailProject = DtProject::select(
            'dt_project_request.*',
            'a.*',
            'b.*'
        )
            ->join('tr_project_request', 'dt_project_request.PRJ_ID', 'tr_project_request.PRJ_ID')
            ->join('ms_transaction_type as a', 'dt_project_request.TRANS_TY_ID', 'a.TRANS_TY_ID')
            ->leftjoin('dt_transaction_type as b', 'dt_project_request.DT_TRANS_TY_ID', 'b.DT_TRANS_TY_ID')
            ->where([
                ['tr_project_request.UUID', $uuid]
            ])
            ->get();
        foreach ($detailProject as $key => $val_DtProject) {
            $data = [
                'TRANSACTION_PRJ_ID' => $val_DtProject->PRJ_ID,
                'TRANSACTION_TYPE' => '[' . $val_DtProject->TRANS_TY_NAME . '] ' . $val_DtProject->DT_TRANS_TY_NAME,
                'TRANSACTION_UUID' => $val_DtProject->UUID,
                'TRANSACTION_SUBJECT' => $val_DtProject->DTPRJ_SUBJECT,
                'TRANSACTION_AMOUNT' => $val_DtProject->DTPRJ_AMOUNT,
                'TRANSACTION_STATUS' => $val_DtProject->STATUS,
                'TRANSACTION_DUE' => $val_DtProject->DTPRJ_DUE_DATE,
                'TRANSACTION_REQUESTED_AT' => $val_DtProject->CREATED_AT,
            ];
            array_push($result, $data);
        }
        return response($result);
    }
    public function index_detail(string $uuid)
    {
        /* Detail project */
        $result = [];
        $detailProject = TransactionProject::select(
            'dt_project_request.*',
            'ms_transaction_type.*',
            'dt_transaction_type.*'
        )
            ->join('dt_project_request', 'dt_project_request.PRJ_ID', '=', 'tr_project_request.PRJ_ID')
            ->join('ms_transaction_type', 'dt_project_request.TRANS_TY_ID', '=', 'ms_transaction_type.TRANS_TY_ID')
            ->join('dt_transaction_type', 'dt_project_request.DT_TRANS_TY_ID', '=', 'dt_transaction_type.DT_TRANS_TY_ID')
            ->where([['tr_project_request.UUID', $uuid]])
            ->get();
        foreach ($detailProject as $key => $valDtProj) {
            array_push($result, [
                "TRANSACTION_UUID" => $valDtProj->UUID,
                "TRANSACTION_TYPE" => "[" . $valDtProj->TRANS_TY_NAME . "] " . $valDtProj->DT_TRANS_TY_NAME,
                "TRANSACTION_SUBJECT" => $valDtProj->DTPRJ_SUBJECT,
                "TRANSACTION_AMOUNT" => $valDtProj->DTPRJ_AMOUNT,
            ]);
        }
        return $result;
    }

    public function modalEditData(string $uuid)
    {
        //
        $result = [
            "Header" => [],
            "Detail" => [],
        ];

        $result['Header'] = TransactionProject::where('UUID', $uuid)->firstOrFail();
        /* CADV Loop */
        $cadv = TransactionProject::select(
            'tr_cash_advanced.APPROVAL_ID',
            'tr_cash_advanced.APPROVAL_CODE_ID',
            'tr_cash_advanced.TRANS_TY_ID',
            'tr_cash_advanced.CADV_ID',
            'tr_cash_advanced.UUID',
            'tr_cash_advanced.CADV_NUMBER',
            'tr_cash_advanced.CADV_SUBJECT',
            'tr_cash_advanced.CADV_NOTES',
            'tr_cash_advanced.CADV_AMOUNT',
            'tr_cash_advanced.CADV_ATTACHMENT',
            'tr_cash_advanced.STATUS',
            'b.TRANS_TY_NAME as TYPE_1',
            'a.TRANS_TY_NAME as TYPE_2',
            'c.DT_TRANS_TY_NAME as TYPE_DT',
        )
            ->where('PRJ_UUID', $uuid)
            ->join('tr_cash_advanced', 'tr_project_request.PRJ_ID', 'tr_cash_advanced.PRJ_ID')
            ->join('ms_transaction_type as a', 'tr_cash_advanced.TRANS_TY_ID', 'a.TRANS_TY_ID')
            ->join('ms_transaction_type as b', 'a.SUB_TRANS_TY_ID', 'b.TRANS_TY_ID')
            ->leftjoin('dt_transaction_type as c', 'tr_project_request.DT_TRANS_TY_ID', 'c.DT_TRANS_TY_ID')
            ->get();
        foreach ($cadv as $keyCadv => $valCadv) {
            $data = [
                'APPROVAL_ID' => $valCadv->APPROVAL_ID,
                'APPROVAL_CODE_ID' => $valCadv->APPROVAL_CODE_ID,
                'TRANSACTION_PRJ_ID' => $valCadv->CADV_ID,
                'TRANSACTION_TYPE' => $valCadv->TYPE_1 . ' [' . $valCadv->TYPE_2 . ']',
                'TRANSACTION_DT_TYPE' => $valCadv->TYPE_DT,
                'TRANSACTION_UUID' => $valCadv->CADV_UUID,
                'TRANSACTION_NUMBER' => $valCadv->CADV_NUMBER,
                'TRANSACTION_SUBJECT' => $valCadv->CADV_SUBJECT,
                'TRANSACTION_NOTES' => $valCadv->CADV_NOTES,
                'TRANSACTION_AMOUNT' => $valCadv->CADV_AMOUNT,
                'TRANSACTION_ATTACHMENT' => $valCadv->CADV_ATTACHMENT,
                'TRANSACTION_STATUS' => $valCadv->STATUS,
            ];
            array_push($result['Detail'], $data);
        }

        /* REIMB Loop */
        $reimb = TransactionProject::select(
            'tr_reimbursement.APPROVAL_ID',
            'tr_reimbursement.APPROVAL_CODE_ID',
            'tr_reimbursement.TRANS_TY_ID',
            'tr_reimbursement.REIMB_ID',
            'tr_reimbursement.UUID',
            'tr_reimbursement.REIMB_NUMBER',
            'tr_reimbursement.REIMB_SUBJECT',
            'tr_reimbursement.REIMB_NOTES',
            'tr_reimbursement.REIMB_AMOUNT',
            'tr_reimbursement.REIMB_ATTACHMENT',
            'tr_reimbursement.STATUS',
        )
            ->where('PRJ_UUID', $uuid)
            ->join('tr_reimbursement', 'tr_project_request.PRJ_ID', 'tr_reimbursement.PRJ_ID')
            ->get();
        foreach ($reimb as $keyReimb => $valReimb) {
            $data = [
                'APPROVAL_ID' => $valReimb->APPROVAL_ID,
                'APPROVAL_CODE_ID' => $valReimb->APPROVAL_CODE_ID,
                'TRANSACTION_TYPE' => $valReimb->TRANS_TY_ID,
                'TRANSACTION_ID' => $valReimb->REIMB_ID,
                'TRANSACTION_UUID' => $valReimb->REIMB_UUID,
                'TRANSACTION_SUBJECT' => $valReimb->REIMB_SUBJECT,
                'TRANSACTION_NUMBER' => $valReimb->REIMB_NUMBER,
                'TRANSACTION_AMOUNT' => $valReimb->REIMB_AMOUNT,
                'TRANSACTION_NOTES' => $valReimb->REIMB_NOTES,
                'TRANSACTION_ATTACHMENT' => $valReimb->REIMB_ATTACHMENT,
                'TRANSACTION_STATUS' => $valReimb->STATUS,
            ];
            array_push($result['Detail'], $data);
        }

        return response($result);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, string $uuid)
    {
        //
        $_Project = TransactionProject::where([['UUID', $uuid]])->firstOrFail();

        /* Project updates */
        $_Project->TRANS_TY_ID = $request->transactionType;
        $_Project->EMPL_ID = $request->employeeId;
        $_Project->PRJ_SUBJECT = $request->subject;
        $_Project->PRJ_NOTES = $request->description;
        $_Project->PRJ_TOTAL_AMOUNT_REQUEST = $request->amount == null ? 0 : $request->amount;
        $_Project->PRJ_REQUEST_DATE = date('Y-m-d');
        $_Project->PRJ_DUE_DATE = date('Y-m-d', strtotime($request->dueDate));
        $_Project->PRJ_CLOSE_DATE = date('Y-m-d', strtotime('+' . $this->maxCloseProject . ' day', strtotime($request->dueDate)));

        /* Attachment */
        $attachment = new AttachmentController;
        $extension = $request->file('attachment')->getExtension();
        $makeAttachment = $attachment->OFuploadImage($request->attachment, 'Project', $extension);
        $_Project->PRJ_ATTTACHMENT = $makeAttachment['filename'];
        $_Project->PRJ_ATTTACHMENT_EXT = $extension;
        $_Project->PRJ_ATTTACHMENT_SIZE = $makeAttachment['size'];
        $_Project->save();
        return response($_Project);
    }

    /**
     * Create project from approval request
     */
    public function createFromApproval($request, string $uuid)
    {
        $_Project = TransactionProject::where([['UUID', $uuid]])->firstOrFail();
        $_company = Company::where([['COMP_CODE', $request->companyCode]])->firstOrFail();
        $_departement = Departement::where([['DEPT_CODE', $request->deptartementCode]])->firstOrFail();
        try {
            DB::transaction(function () use ($request, $_Project, $_company, $_departement, $uuid) {
                /* Project updates */
                $_Project->TRANS_TY_ID = $request->transactionType;
                $_Project->EMPL_ID = $request->employeeId;
                $_Project->DEPT_ID = $_company->COMP_ID;
                $_Project->COMP_ID = $_departement->DEPT_ID;
                $_Project->PRJ_SUBJECT = $request->subject;
                $_Project->PRJ_NOTES = $request->description;
                $_Project->PRJ_TOTAL_AMOUNT_REQUEST = $request->amountRequest == null ? 0 : $request->amountRequest;
                $_Project->PRJ_REQUEST_DATE = date('Y-m-d H:i:s');
                $_Project->PRJ_DUE_DATE = date('Y-m-d', strtotime($request->dueDate));
                $_Project->PRJ_CLOSE_DATE = date('Y-m-d H:i:s', strtotime('+' . $this->maxCloseProject . ' day', strtotime($request->dueDate)));
                // $_Project->PRJ_ATTTACHMENT = $request->;
                // $_Project->PRJ_ATTTACHMENT_EXT = $request->;
                // $_Project->PRJ_ATTTACHMENT_SIZE = $request->;
                $_Project->save();
            });
            DB::commit();
            return response($_Project, 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            /* Return Response on error */
            return response([
                "error" => $e->getMessage(),
                "message" => "Sorry, System can't receive your request",
            ], 500);
        }
    }

    public function create_header(Request $request)
    {
        $idConfig = [
            'table' => 'tr_project_request',
            'field' => 'PRJ_NUMBER',
            'length' => 11,
            'prefix' => date('Ym') . '/',
            'reset_on_prefix_change' => true
        ];
        if ($request->dept['compCode'] !== null && $request->dept['deptCode'] !== null) {
            $idConfig = [
                'table' => 'tr_project_request',
                'field' => 'PRJ_NUMBER',
                'length' => 17,
                'prefix' => $request->dept['compCode'] . '/' . $request->dept['deptCode'] . '/' . date('Ym') . '/',
                'reset_on_prefix_change' => true
            ];
        }

        $transaction = new TransactionProject;
        $newData = $transaction->create([
            "PRJ_NUMBER" => IdGenerator::generate($idConfig),
            "EMPL_ID" => $request->emplId,
            "COMP_ID" => $request->dept['compId'],
            "DEPT_ID" => $request->dept['deptId'],
        ]);
        if (!$newData) {
            return response()->json([
                'status' => false,
                'message' => "Error inputed data"
            ], 500);
        }
        $data = $transaction->select(
            'tr_project_request.*',
            'ms_company.COMP_CODE',
            'ms_company.COMP_NAME',
            'ms_departement.DEPT_CODE',
            'ms_departement.DEPT_NAME'
        )
            ->leftJoin('ms_company', 'tr_project_request.COMP_ID', '=', 'ms_company.COMP_ID')
            ->leftJoin('ms_departement', 'tr_project_request.DEPT_ID', '=', 'ms_departement.DEPT_ID')
            ->where([['PRJ_ID', $newData->PRJ_ID]])
            ->firstOrFail();

        return response($data);
    }

    // public function create_detail(Request $request)
    // {
    //     /* Input Validation */
    //     $validateData = Validator::make(
    //         $request->all(),
    //         [
    //             'group1' => 'required',
    //             'companyCode' => 'required',
    //             'deptartementCode' => 'required',
    //         ]
    //     );
    //     if ($validateData->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Data Validation Error',
    //             'errors' => $validateData->errors()
    //         ]);
    //     }

    //     /* Explode Grouping ID */
    //     $transType = $request->transactionType;
    //     $transCode = explode("_", $request->group1);
    //     $transTypePrimary = $transCode[array_key_first($transCode)];
    //     $transTypeSecondary = $transCode[array_key_last($transCode)];
    //     if ($request->secondaryGroup != null) {
    //         $transTypeSecondary = $request->secondaryGroup;
    //     }
    //     $_Project = TransactionProject::where([['UUID', $request->projectUuid]])->firstOrFail();
    // }

    public function create_detail(Request $request)
    {
        /* Set Update Project Amount */
        $_Project = TransactionProject::where([['UUID', $request->projectUuid]])->firstOrFail();
        try {
            DB::transaction(function () use ($request, $_Project) {
                activity('Authentication')
                    ->causedBy(Auth::user())
                    ->event('')
                    ->log('Create detail project ' . $_Project->PRJ_NUMBER);

                DtProject::create([
                    "PRJ_ID" => $_Project->PRJ_ID,
                    "TRANS_TY_ID" => $request->group2,
                    "DT_TRANS_TY_ID" => $request->group3,
                    "DTPRJ_SUBJECT" => $request->subject,
                    "DTPRJ_AMOUNT" => $request->amountRequest,
                    "DTPRJ_TOTAL_AMOUNT_USED" => $request->amountRequest,
                    "DTPRJ_DUE_DATE" => $request->dueDate,
                ]);
            });

            /* Update project amount */
            // $_Project->PRJ_TOTAL_AMOUNT_USED = $_Project->PRJ_TOTAL_AMOUNT_USED + $request->amountRequest;
            $_Project->PRJ_TOTAL_AMOUNT_REQUEST = $_Project->PRJ_TOTAL_AMOUNT_REQUEST + $request->amountRequest;
            $_Project->save();

            DB::commit();
            return response([], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            /* Return Response on error */
            return response([
                "error" => $e->getMessage(),
                "message" => "Sorry, System can't receive your request",
            ], 500);
        }

        // return response($request);
        // return response($_Project);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update_header(Request $request, string $uuid)
    {
        // $deptCode = $request->
        $getProject = TransactionProject::where('UUID', $uuid)->firstOrFail();

        if ($request->companyCode !== null && $request->departCode !== null) {
            $_company = Company::select('COMP_ID')->where([['COMP_CODE', $request->companyCode]])->firstOrFail();
            $_departement = Departement::select('DEPT_ID')->where([['DEPT_CODE', $request->departCode]])->firstOrFail();
            $idConfig = [
                'table' => 'tr_project_request',
                'field' => 'PRJ_NUMBER',
                'length' => 17,
                'prefix' => $request->companyCode . '/' . $request->departCode . '/' . date('Ym') . '/',
                'reset_on_prefix_change' => true
            ];
            $getProject->COMP_ID = $_company->COMP_ID;
            $getProject->DEPT_ID = $_departement->DEPT_ID;
        } else {
            $idConfig = [
                'table' => 'tr_project_request',
                'field' => 'PRJ_NUMBER',
                'length' => 11,
                'prefix' => date('Ym') . '/',
                'reset_on_prefix_change' => true
            ];
            $getProject->COMP_ID = null;
            $getProject->DEPT_ID = null;
        }

        $getProject->PRJ_NUMBER = IdGenerator::generate($idConfig);
        $getProject->save();

        return response($getProject);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy_project(Request $request, string $uuid)
    {
        $_Project = TransactionProject::where([['UUID', $uuid]])->firstOrFail();

        $validateUser = Validator::make(
            $request->all(),
            [
                'deleteReason' => 'required',
            ],
            [
                'deleteReason.required' => "Complete your delete reason"
            ]
        );

        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Input Validation Error',
                'errors' => $validateUser->errors()
            ], 400);
        }
        try {
            DB::transaction(function () use ($request, $_Project) {
                /* Project updates */
                $_Project->TRANS_TY_ID = $_Project->transactionType;
                $_Project->PRJ_DELETE = 1;
                $_Project->PRJ_DELETE_DATE = date('Y-m-d H:i:s');
                $_Project->PRJ_DELETE_REASON = $request->deleteReason;
                $_Project->PRJ_DELETE_BY = Auth::user();

                $_Project->save();

                /* Log */
                activity('Transaction')
                    ->causedBy(Auth::user())
                    ->event('Delete')
                    ->performedOn($_Project)
                    ->log('Delete project request transaction');
            });
            DB::commit();
            return response($_Project, 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            /* Return Response on error */
            return response([
                "error" => $e->getMessage(),
                "message" => "Sorry, System can't receive your request",
            ], 500);
        }
    }
}
