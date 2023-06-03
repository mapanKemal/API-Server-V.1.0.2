<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;
    protected $table = 'ms_company';
    protected $primaryKey = 'COMP_ID';

    protected $fillable = [
        "COMP_CODE",
        "COMP_NAME",
    ];

    public $timestamps = false;
}
