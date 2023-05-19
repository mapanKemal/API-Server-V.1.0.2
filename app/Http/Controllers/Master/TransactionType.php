<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\DetailTransactionType;
use App\Models\Master\TransactionType as MasterTransactionType;
use Illuminate\Http\Request;

class TransactionType extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
    // public function show(string $id)
    // {
    //     //
    // }
    public function show_projectType(string $id)
    {
        //
        $result = [];
        $transMType = MasterTransactionType::select('TRANS_TY_ID', 'TRANS_TY_NAME')->where('SUB_TRANS_TY_ID', $id)->get();
        foreach ($transMType as $keyMType => $valMType) {
            $transSbType = MasterTransactionType::select('TRANS_TY_ID', 'TRANS_TY_NAME')->where('SUB_TRANS_TY_ID', $valMType->TRANS_TY_ID)->get();
            foreach ($transSbType as $keySbType => $valSbType) {
                $res = [
                    "value" => $valSbType->TRANS_TY_ID,
                    "label" => $valMType->TRANS_TY_NAME . ' [' . $valSbType->TRANS_TY_NAME . ']'
                ];
                array_push($result, $res);
            }
        }
        return response($result);
    }
    public function show_projectSubType(string $id)
    {
        //
        $result = [];
        $transMtSubtype = MasterTransactionType::select('TRANS_TY_ID', 'TRANS_TY_NAME')->where('SUB_TRANS_TY_ID', $id)->get();
        foreach ($transMtSubtype as $keyMtSubtype => $valMtSubtype) {
            $res = [
                "value"   => $valMtSubtype->TRANS_TY_ID,
                "label"   => $valMtSubtype->TRANS_TY_NAME,
            ];
            array_push($result, $res);
        }
        return response($result);
    }
    public function show_projectSubDtType(string $id)
    {
        //
        $result = [];
        $dt_transMtSubType = DetailTransactionType::select('DT_TRANS_TY_ID', 'DT_TRANS_TY_NAME')->where('TRANS_TY_ID', $id)->get();
        foreach ($dt_transMtSubType as $keyMtSubType => $valMtSubType) {
            $res = [
                "value" => $valMtSubType->DT_TRANS_TY_ID,
                "label" => $valMtSubType->DT_TRANS_TY_NAME,
            ];
            array_push($result, $res);
        }
        return response($result);
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
