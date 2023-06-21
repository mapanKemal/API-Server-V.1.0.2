<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Detail_Structure extends Model
{
    use HasFactory;
    protected $table = "dt_structural";
    protected $primaryKey = "MEMBER_EMP_POST";

    protected $fillable = [
        "MEMBER_EMP_POST",
        "LEADER_EMP_POST",
        "STRUCTURE_ID",
        "STRUCTURE_NUMBER",
        "APPROVE_LOWER",
        "APPROVE_LOWER_THAN",
        "APPROVE_UPPER",
        "APPROVE_UPPER_THAN",
        "ACTIVE",
    ];
}
