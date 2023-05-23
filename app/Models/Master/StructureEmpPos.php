<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StructureEmpPos extends Model
{
    use HasFactory;
    protected $table = 'fr_emp_position';
    protected $primaryKey = 'FR_POST_ID';

    protected $fillable = [
        "EMPL_ID",
        "COMP_ID",
        "DEPT_ID",
        "STRUCTURE_CODE",
        "USER_ID",
        "POST_ID",
        "STRUCTURE_NUMBERING",
        "ACCP_LOWER",
        "ACCP_LOWER_AMOUNT",
        "ACCP_UPPER",
        "ACCP_UPPER_AMOUNT",
        "START_DATE",
        "END_DATE",
        "STATUS",
    ];
}
