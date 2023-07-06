<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalCode extends Model
{
    use HasFactory;
    protected $table = 'ms_approval_code';
    protected $primaryKey = 'APPROVAL_CODE_ID';

    protected $fillable = [
        "APPROVAL_CODE_DESC",
        "SYS_APPROVAL_VARIANT",
        "APPROVAL_CODE",
    ];

    public $timestamps = false;
}
