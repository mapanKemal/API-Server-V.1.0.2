<?php

namespace App\Models\Transaction;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DtProject extends Model
{
    use HasFactory;
    protected $table = 'dt_project_request';
    protected $primaryKey = 'DTPRJ_ID';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "DTPRJ_SUBJECT",
        "DTPRJ_AMOUNT",
        "DTPRJ_TOTAL_AMOUNT_USED",
        "DTPRJ_DIFF_AMOUNT",
        "DTPRJ_CLOSE",
        "DTPRJ_CLOSE_DATE",
        "DTPRJ_DELETE",
        "DTPRJ_DELETE_DATE",
        "DTPRJ_TRANS_RESPONSE",
        "DTPRJ_ATTTACHMENT",
        "DTPRJ_ATTTACHMENT_EXT",
        "DTPRJ_ATTTACHMENT_SIZE",
        "STATUS",
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];
}
