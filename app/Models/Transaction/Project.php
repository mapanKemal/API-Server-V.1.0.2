<?php

namespace App\Models\Transaction;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;
    protected $table = 'tr_project_request';
    protected $primaryKey = 'PRJ_ID';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "TRANS_TY_ID",
        "DT_TRANS_TY_ID",
        "APPROVAL_ID",
        "EMPL_ID",
        "DEPT_ID",
        "COMP_ID",
        "PRJ_NUMBER",
        "PRJ_SUBJECT",
        "PRJ_NOTES",
        "PRJ_TOTAL_AMOUNT_REQUEST",
        "PRJ_TOTAL_AMOUNT_USED",
        "PRJ_DIFF_AMOUNT",
        "PRJ_REQUEST_DATE",
        "PRJ_COMPLETE_DATE",
        "PRJ_ATTTACHMENT",
        "PRJ_ATTTACHMENT_EXT",
        "PRJ_ATTTACHMENT_SIZE",
        "PRJ_DUE_DATE",
        "PRJ_CLOSE",
        "PRJ_CLOSE_DATE",
        "PRJ_CLOSE_REASON",
        "PRJ_CLOSE_BY",
        "PRJ_DELETE",
        "PRJ_DELETE_DATE",
        "PRJ_DELETE_REASON",
        "PRJ_DELETE_BY",
        "STATUS",
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        // 'PRJ_ID'
    ];
}
