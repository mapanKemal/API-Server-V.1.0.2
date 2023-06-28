<?php

namespace App\Models\Approvals;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Structure_Approval extends Model
{
    use HasFactory;
    protected $table = 'structure_on_approval';
    protected $primaryKey = 'STRUCTURE_ID';
    public $incrementing = false;

    protected $fillable = [
        "STRUCTURE_ID",
        "APPROVAL_ID",
        "STURCT_APP_SEQUENCE",
    ];
}
