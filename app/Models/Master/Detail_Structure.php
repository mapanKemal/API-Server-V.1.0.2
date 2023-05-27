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
        "ACTIVE",
    ];
}
