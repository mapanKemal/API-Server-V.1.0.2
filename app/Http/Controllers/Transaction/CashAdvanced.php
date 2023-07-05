<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Transaction\CashAdvanced as TransactionCashAdvanced;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class CashAdvanced extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }
    public function indexBy()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create_fromProject($createCad, $other = [])
    {
        //
        $idConfig = [
            'table' => 'tr_cash_advanced',
            'field' => 'CADV_NUMBER',
            'length' => 21,
            'prefix' =>  'CAD/' . $other['COMP_CODE'] . '/' . $other['DEPT_CODE'] . '/' . date('Ym') . '/'
        ];
        $createCad["CADV_NUMBER"] = IdGenerator::generate($idConfig);
        $_createTransaction = TransactionCashAdvanced::create($createCad);

        return $_createTransaction;
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
