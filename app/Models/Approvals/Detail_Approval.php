<?php

namespace App\Models\Approvals;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Detail_Approval extends Model
{
    use HasFactory;
    protected $table = 'dt_approval';
    protected $primaryKey = 'DT_APPR_ID';

    protected $fillable = [
        "USER_ID",
        "EMPL_ID",
        "APPROVAL_ID",
        "APPROVAL_CODE_ID",
        "DT_APPR_REQ_DATE",
        "DT_APPR_DATE",
        "DT_APPR_DESCRIPTION",
        "DT_APPR_DEACTIVE",
        "STATUS",
        "NOTIFICATION_RESPONSE",
    ];
}
