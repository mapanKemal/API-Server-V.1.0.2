<?php

namespace App\Models\Transaction;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashAdvanced extends Model
{
    use HasFactory;
    protected $table = 'tr_cash_advanced';
    protected $primaryKey = 'CADV_ID';

    protected $fillable = [
        "TRANS_TY_ID",
        "EMPL_ID",
        "PRJ_ID",
        "APPROVAL_ID",
        "APPROVAL_CODE_ID",
        "DT_TRANS_TY_ID",
        "CADV_UUID",
        "CADV_NUMBER",
        "CADV_SUBJECT",
        "CADV_NOTES",
        "CADV_AMOUNT",
        "CADV_AMOUNT_REAL",
        "CADV_AMOUNT_REAL_DIFF",
        "CADV_ATTACHMENT",
        "CADV_ATTACHMENT_SIZE",
        "CADV_CLOSE",
        "CADV_CLOSE_DATE",
        "CADV_CLOSE_REASON",
        "CADV_CLOSE_BY",
        "CADV_DELETE",
        "CADV_DELETE_DATE",
        "CADV_DELETE_REASON",
        "CADV_DELETE_BY",
        "CADV_TRANSFER_SCHEDULE",
        "CADV_BANK_ACCOUNT",
        "CADV_TRANS_RESPONSE",
        "STATUS",
    ];
}
