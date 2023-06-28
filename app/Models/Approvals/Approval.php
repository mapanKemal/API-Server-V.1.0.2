<?php

namespace App\Models\Approvals;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    use HasFactory;
    protected $table = 'tr_approval';
    protected $primaryKey = 'APPROVAL_ID';

    protected $fillable = [
        "COMP_ID",
        "DEPT_ID",
        "STATUS",
        "APPR_FINAL_DATE",
        "APPR_DEACTIVE",
        "APPR_DEACTIVE_DATE",
        "APPR_DEACTIVE_REASON",
        "LOG_ACTIVITY",
    ];
}
