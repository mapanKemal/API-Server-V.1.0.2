<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Master\Company;
use App\Models\Master\Departement;
use App\Models\Transaction\Project as TransactionProject;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Http\Request;
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
    public function index_newTransaction()
    {
        //
        return response(TransactionProject::select(
            "tr_project_request.*",
            "ms_company.COMP_CODE",
            "ms_departement.DEPT_CODE",
        )
            ->leftJoin('ms_company', 'tr_project_request.COMP_ID', '=', 'ms_company.COMP_ID')
            ->leftJoin('ms_departement', 'tr_project_request.DEPT_ID', '=', 'ms_departement.DEPT_ID')
            ->get());
    }
    public function index_transByHeader(string $uuid)
    {
        //
        return response(TransactionProject::select(
            "tr_project_request.*",
            "ms_company.COMP_CODE",
            "ms_departement.DEPT_CODE",
        )
            ->join('ms_company', 'tr_project_request.COMP_ID', '=', 'ms_company.COMP_ID')
            ->join('ms_departement', 'tr_project_request.DEPT_ID', '=', 'ms_departement.DEPT_ID')
            ->where('UUID', $uuid)
            ->firstOrFail());
    }
    public function index_detailByHeader(string $uuid)
    {
        //
        $result = [
            "HEADER" => [],
            "DETAIL" => [],
        ];

        $result['HEADER'] = TransactionProject::where('UUID', $uuid)->firstOrFail();
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
            'tr_cash_advanced.CREATED_AT',
            'b.TRANS_TY_NAME as TYPE_1',
            'a.TRANS_TY_NAME as TYPE_2',
            'c.DT_TRANS_TY_NAME as TYPE_DT',
        )
            ->where('tr_project_request.UUID', $uuid)
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
                'TRANSACTION_UUID' => $valCadv->UUID,
                'TRANSACTION_NUMBER' => $valCadv->CADV_NUMBER,
                'TRANSACTION_SUBJECT' => $valCadv->CADV_SUBJECT,
                'TRANSACTION_NOTES' => $valCadv->CADV_NOTES,
                'TRANSACTION_AMOUNT' => $valCadv->CADV_AMOUNT,
                'TRANSACTION_ATTACHMENT' => $valCadv->CADV_ATTACHMENT,
                'TRANSACTION_STATUS' => $valCadv->STATUS,
                'TRANSACTION_REQUESTED_AT' => $valCadv->CREATED_AT,
            ];
            array_push($result['DETAIL'], $data);
        }

        /* REIMB Loop */
        // $reimb = TransactionProject::select(
        //     'tr_reimbursement.APPROVAL_ID',
        //     'tr_reimbursement.APPROVAL_CODE_ID',
        //     'tr_reimbursement.TRANS_TY_ID',
        //     'tr_reimbursement.REIMB_ID',
        //     'tr_reimbursement.UUID',
        //     'tr_reimbursement.REIMB_NUMBER',
        //     'tr_reimbursement.REIMB_SUBJECT',
        //     'tr_reimbursement.REIMB_NOTES',
        //     'tr_reimbursement.REIMB_AMOUNT',
        //     'tr_reimbursement.REIMB_ATTACHMENT',
        //     'tr_reimbursement.STATUS',
        // )
        //     ->where('tr_project_request.UUID', $uuid)
        //     ->join('tr_reimbursement', 'tr_project_request.PRJ_ID', 'tr_reimbursement.PRJ_ID')
        //     ->join('ms_transaction_type as a', 'tr_reimbursement.TRANS_TY_ID', 'a.TRANS_TY_ID')
        //     ->join('ms_transaction_type as b', 'a.SUB_TRANS_TY_ID', 'b.TRANS_TY_ID')
        //     ->get();
        // foreach ($reimb as $keyReimb => $valReimb) {
        //     $data = [
        //         'APPROVAL_ID' => $valReimb->APPROVAL_ID,
        //         'APPROVAL_CODE_ID' => $valReimb->APPROVAL_CODE_ID,
        //         'TRANSACTION_TYPE' => $valReimb->TRANS_TY_ID,
        //         'TRANSACTION_ID' => $valReimb->REIMB_ID,
        //         'TRANSACTION_UUID' => $valReimb->UUID,
        //         'TRANSACTION_SUBJECT' => $valReimb->REIMB_SUBJECT,
        //         'TRANSACTION_NUMBER' => $valReimb->REIMB_NUMBER,
        //         'TRANSACTION_AMOUNT' => $valReimb->REIMB_AMOUNT,
        //         'TRANSACTION_NOTES' => $valReimb->REIMB_NOTES,
        //         'TRANSACTION_ATTACHMENT' => $valReimb->REIMB_ATTACHMENT,
        //         'TRANSACTION_STATUS' => $valReimb->STATUS,
        //     ];
        //     array_push($result['Detail'], $data);
        // }

        return response($result);
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
        $_company = Company::where([['COMP_CODE', $request->companyCode]])->firstOrFail();
        $_departement = Departement::where([['DEPT_CODE', $request->deptartementCode]])->firstOrFail();

        /* Project updates */
        $_Project->TRANS_TY_ID = $request->transactionType;
        $_Project->EMPL_ID = $request->employeeId;
        $_Project->DEPT_ID = $_company->COMP_ID;
        $_Project->COMP_ID = $_departement->DEPT_ID;
        $_Project->PRJ_SUBJECT = $request->subject;
        $_Project->PRJ_NOTES = $request->description;
        $_Project->PRJ_TOTAL_AMOUNT_REQUEST = $request->amountRequest;
        $_Project->PRJ_REQUEST_DATE = date('Y-m-d');
        $_Project->PRJ_DUE_DATE = date('Y-m-d', strtotime($request->dueDate));
        $_Project->PRJ_CLOSE_DATE = date('Y-m-d', strtotime('+' . $this->maxCloseProject . ' day', strtotime($request->dueDate)));
        // $_Project->PRJ_ATTTACHMENT = $request->;
        // $_Project->PRJ_ATTTACHMENT_EXT = $request->;
        // $_Project->PRJ_ATTTACHMENT_SIZE = $request->;
        $_Project->save();
        return response($_Project);
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
        ]);
        if (!$newData) {
            return response()->json([
                'status' => false,
                'message' => "Error inputed data"
            ], 500);
        }
        $data = $transaction->select('UUID', 'PRJ_NUMBER', 'EMPL_ID')
            ->where([['PRJ_ID', $newData->PRJ_ID]])
            ->firstOrFail();

        return response($data);
    }

    public function create_detail(Request $request)
    {
        /* Input Validation */
        $validateData = Validator::make(
            $request->all(),
            [
                'group1' => 'required',
                'companyCode' => 'required',
                'deptartementCode' => 'required',
            ]
        );
        if ($validateData->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Data Validation Error',
                'errors' => $validateData->errors()
            ]);
        }

        /* Explode Grouping ID */
        $transType = $request->transactionType;
        $transCode = explode("_", $request->group1);
        $transTypePrimary = $transCode[array_key_first($transCode)];
        $transTypeSecondary = $transCode[array_key_last($transCode)];
        if ($request->secondaryGroup != null) {
            $transTypeSecondary = $request->secondaryGroup;
        }

        /* Set Update Project Amount */
        $_Project = TransactionProject::where([['UUID', $request->projectUuid]])->firstOrFail();
        $_company = Company::where([['COMP_CODE', $request->companyCode]])->firstOrFail();
        $_departement = Departement::where([['DEPT_CODE', $request->deptartementCode]])->firstOrFail();

        if ($transTypePrimary == $this->advance_transTyId) {
            /* If Transaction Is CASH ADVANCED */
            $advanced = new CashAdvanced;
            $createTrans = $advanced->create_fromProject([
                "EMPL_ID" => $request->employeeId,
                "TRANS_TY_ID" => $transTypeSecondary,
                "DT_TRANS_TY_ID" => $request->detailingGroup,
                "PRJ_ID" => $_Project->PRJ_ID,
                "CADV_SUBJECT" => $request->subject,
                "CADV_NOTES" => $request->description,
                "CADV_AMOUNT" => $request->amountRequest,
                "CADV_DUE_DATE" => $request->dueDate,
                "CADV_ATTACHMENT" => null,
                "CADV_ATTACHMENT_SIZE" => 0,
            ], [
                "COMP_ID" => $_company->COMP_ID,
                "COMP_CODE" => $request->companyCode,

                "DEPT_ID" => $_departement->DEPT_ID,
                "DEPT_CODE" => $request->deptartementCode,
            ]);
        } elseif ($transTypePrimary == $this->reimburse_transTyId) {
            /* If Transaction Is REIMBURSEMENT */
        }

        $_Project->PRJ_TOTAL_AMOUNT_USED = $_Project->PRJ_TOTAL_AMOUNT_USED + $request->amountRequest;
        $_Project->save();

        // return response($request);
        return response($_Project);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update_header(Request $request, string $uuid)
    {
        // $deptCode = $request->

        $idConfig = [
            'table' => 'tr_project_request',
            'field' => 'PRJ_NUMBER',
            'length' => 11,
            'prefix' => date('Ym') . '/',
            'reset_on_prefix_change' => true
        ];
        if ($request->companyCode !== null && $request->departCode !== null) {
            $idConfig = [
                'table' => 'tr_project_request',
                'field' => 'PRJ_NUMBER',
                'length' => 17,
                'prefix' => $request->companyCode . '/' . $request->departCode . '/' . date('Ym') . '/',
                'reset_on_prefix_change' => true
            ];
        }
        $getProject = TransactionProject::where('UUID', $uuid)->firstOrFail();
        $getProject->PRJ_NUMBER = IdGenerator::generate($idConfig);
        $getProject->save();

        return response($getProject);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
