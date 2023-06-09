<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeePosition extends Model
{
    use HasFactory;
    protected $table = 'fr_employee_position';
    protected $primaryKey = 'FR_POST_ID';

    protected $fillable = [
        "USER_ID",
        "EMPL_ID",
        "COMP_ID",
        "DEPT_ID",
        "POST_ID",
        "ACCP_LOWER",
        "ACCP_LOWER_AMOUNT",
        "ACCP_UPPER",
        "ACCP_UPPER_AMOUNT",
        "START_DATE",
        "END_DATE",
        "ON_STRUCTURE",
        "ACTIVE",
    ];
}
