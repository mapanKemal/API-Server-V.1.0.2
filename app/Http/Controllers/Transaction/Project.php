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
        return response(TransactionProject::all());
    }
    public function index_transByHeader(string $uuid)
    {
        //
        return response(TransactionProject::where('PRJ_UUID', $uuid)->firstOrFail());
    }
    public function modalEditData(string $uuid)
    {
        //
        $result = [
            "Header" => [],
            "Detail" => [],
        ];

        $result['Header'] = TransactionProject::where('PRJ_UUID', $uuid)->firstOrFail();
        /* CADV Loop */
        $cadv = TransactionProject::select(
            'tr_cash_advanced.APPROVAL_ID',
            'tr_cash_advanced.APPROVAL_CODE_ID',
            'tr_cash_advanced.TRANS_TY_ID',
            'tr_cash_advanced.CADV_ID',
            'tr_cash_advanced.CADV_UUID',
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
            'tr_reimbursement.REIMB_UUID',
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
    public function create()
    {
        //
    }

    public function create_header(Request $request)
    {
        $idConfig = [
            'table' => 'tr_project_request',
            'field' => 'PRJ_NUMBER',
            'length' => 12,
            'prefix' => date('Ym') . '/',
            'reset_on_prefix_change' => true
        ];
        if ($request->compCode && $request->deptCode) {
            $idConfig = [
                'table' => 'tr_project_request',
                'field' => 'PRJ_NUMBER',
                'length' => 16,
                'prefix' => $request->compCode . '/' . $request->deptCode . '/' . date('Ym') . '/',
                'reset_on_prefix_change' => true
            ];
        }
        $newData = TransactionProject::create([
            "PRJ_NUMBER" => IdGenerator::generate($idConfig),
            "EMPL_ID" => $request->emplId,
            "PRJ_UUID" => Uuid::uuid4(),
        ]);

        return response($newData);
    }

    public function create_detail(Request $request)
    {
        /* Input Validation */
        $validateData = Validator::make(
            $request->all(),
            [
                'primaryGroup' => 'required',
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
        $tyCode = explode("_", $request->primaryGroup);
        $tyCompare = $tyCode[array_key_first($tyCode)];
        $tyId = $tyCode[array_key_last($tyCode)];
        if ($request->secondaryGroup != null) {
            $tyId = $request->secondaryGroup;
        }

        if ($tyCompare == $this->advance_transTyId) {
            /* If Transaction Is CASH ADVANCED */
            $advanced = new CashAdvanced;
            $createTrans = $advanced->create_fromProject([
                "EMPL_ID" => $request->emplId,
                "TRANS_TY_ID" => $tyId,
                "DT_TRANS_TY_ID" => $request->detailingGroup,
                "PRJ_ID" => $request->projectId,
                "CADV_SUBJECT" => $request->trans_subject,
                "CADV_NOTES" => $request->trans_Notes,
                "CADV_AMOUNT" => $request->trans_Amount,
                "CADV_DUE_DATE" => $request->trans_DueDate,
                "CADV_ATTACHMENT" => null,
                "CADV_ATTACHMENT_SIZE" => 0,
            ], [
                "COMP_CODE" => $request->compCode,
                "DEPT_CODE" => $request->deptCode,
            ]);
        } elseif ($tyCompare == $this->reimburse_transTyId) {
            /* If Transaction Is REIMBURSEMENT */
        }

        /* Set Update Project Amount */
        $_Project = TransactionProject::find($request->projectId);
        $_Project->PRJ_TOTAL_AMOUNT_USED = $_Project->PRJ_TOTAL_AMOUNT_USED + $request->trans_Amount;
        $_Project->save();

        return response($_Project);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update_header(Request $request, string $uuid)
    {
        $idConfig = [
            'table' => 'tr_project_request',
            'field' => 'PRJ_NUMBER',
            'length' => 17,
            'prefix' => $request->compCode . '/' . $request->deptCode . '/' . date('Ym') . '/',
            'reset_on_prefix_change' => true
        ];
        $getProject = TransactionProject::where('PRJ_UUID', $uuid)->firstOrFail();
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
