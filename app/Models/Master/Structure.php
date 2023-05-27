<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Structure extends Model
{
    use HasFactory;
    protected $table = "ms_structural";
    protected $primaryKey = "STRUCTURE_ID";

    protected $fillable = [
        "COMP_ID",
        "DEPT_ID",
        "STRUCTURE_NAME",
        "STRUCTURE_DESCRIPTION",
        "SYSTEM_DEFAULT",
        "STATUS",
        "LOG_ACTIVITY",
    ];
}
