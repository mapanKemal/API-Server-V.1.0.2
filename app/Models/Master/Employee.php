<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;
    protected $table = 'ms_employee';
    protected $primaryKey = 'EMPL_ID';

    protected $fillable = [
        "USER_ID",
        "EMPL_NUMBER",
        "EMPL_UNIQUE_CODE",
        "EMPL_FIRSTNAME",
        "EMPL_LASTNAME",
        "EMPL_GENDER",
        "STATUS",
        "EMPL_CONFIG",
    ];
}
