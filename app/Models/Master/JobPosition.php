<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPosition extends Model
{
    use HasFactory;
    protected $table = 'ms_position';
    protected $primaryKey = 'POST_ID';

    protected $fillable = [
        "POST_NAME",
        "POST_STRUCTURE_LIMIT",
        "POST_APPROVAL_SET",
        "POST_NUMBER",
        "SYSTEM_DEFAULT",
        "STATUS",
    ];

    public $timestamps = false;
}
