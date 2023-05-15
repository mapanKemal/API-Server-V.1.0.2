<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Transaction\Project as TransactionProject;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class Project extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function show_newTransaction()
    {

        return response(TransactionProject::all());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create_newTransaction()
    {
        $idConfig = [];
        $newData = TransactionProject::create([
            "TRANS_TY_ID" => '',
            "DT_TRANS_TY_ID" => '',
            "PRJ_UUID" => Uuid::uuid4(),
            "PRJ_NUMBER" => IdGenerator::generate($idConfig),
            "PRJ_SUBJECT" => '',
            "PRJ_NOTES" => '',
            "PRJ_TOTAL_AMOUNT_REQUEST" => '',
            "PRJ_TOTAL_AMOUNT_USED" => '',
            "PRJ_DIFF_AMOUNT" => '',
            "PRJ_REQUEST_DATE" => '',
            "PRJ_COMPLETE_DATE" => '',
            "PRJ_ATTTACHMENT" => '',
            "PRJ_ATTTACHMENT_SIZE" => '',
            "PRJ_CLOSE" => '',
            "PRJ_CLOSE_DATE" => '',
            "PRJ_CLOSE_REASON" => '',
            "PRJ_CLOSE_BY" => '',
            "PRJ_DELETE" => '',
            "PRJ_DELETE_DATE" => '',
            "PRJ_DELETE_REASON" => '',
            "PRJ_DELETE_BY" => '',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */


    /**
     * Display the specified resource.
     */


    /**
     * Show the form for editing the specified resource.
     */


    /**
     * Update the specified resource in storage.
     */


    /**
     * Remove the specified resource from storage.
     */
}
