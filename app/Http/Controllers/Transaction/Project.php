<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Master\Company;
use App\Models\Master\Departement;
use App\Models\Transaction\Project as TransactionProject;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class Project extends Controller
{
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
        )
            ->where('PRJ_UUID', $uuid)
            ->join('tr_cash_advanced', 'tr_project_request.PRJ_ID', 'tr_cash_advanced.PRJ_ID')
            ->get();
        foreach ($cadv as $keyCadv => $valCadv) {
            $data = [
                'APPROVAL_ID' => $valCadv->APPROVAL_ID,
                'APPROVAL_CODE_ID' => $valCadv->APPROVAL_CODE_ID,
                'TRANSACTION_TYPE' => $valCadv->TRANS_TY_ID,
                'TRANSACTION_ID' => $valCadv->CADV_ID,
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
        $dept = Departement::where('DEPT_ID', $request->deptId)->first();
        $comp = Company::where('COMP_ID', $request->compId)->first();
        $idConfig = [
            'table' => 'tr_project_request',
            'field' => 'PRJ_NUMBER',
            'length' => 16,
            'prefix' => $comp->COMP_CODE . '/' . $dept->DEPT_CODE . '/' . date('Ym') . '/'
        ];
        $newData = TransactionProject::create([
            // "TRANS_TY_ID" => '',
            // "DT_TRANS_TY_ID" => '',
            "EMPL_ID" => $request->emplId,
            "PRJ_UUID" => Uuid::uuid4(),
            "PRJ_NUMBER" => IdGenerator::generate($idConfig),
            "PRJ_SUBJECT" => '',
            "PRJ_NOTES" => '',
            "PRJ_ATTTACHMENT" => '',
        ]);
        // $newData = [
        //     "EMPL_ID" => $request->empId,
        //     "PRJ_UUID" => Uuid::uuid4(),
        //     "PRJ_NUMBER" => IdGenerator::generate($idConfig),
        // ];
        return response($newData);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
