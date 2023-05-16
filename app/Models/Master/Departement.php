<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departement extends Model
{
    use HasFactory;
    protected $table = 'ms_departement';
    protected $primaryKey = 'DEPT_ID';

    protected $fillable = [
        "DEPT_CODE",
        "DEPT_NAME",
    ];
}
