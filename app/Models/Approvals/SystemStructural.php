<?php

namespace App\Models\Approvals;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemStructural extends Model
{
    use HasFactory;
    protected $table = 'default_structural_system';
    protected $primaryKey = 'DEF_SSID';

    protected $fillable = [
        "TRANS_TY_ID",
        "STRUCTURE_ID",
        "ACTIVE",
        "STURCT_APP_SEQUENCE",
    ];

    public $timestamps = false;
}
