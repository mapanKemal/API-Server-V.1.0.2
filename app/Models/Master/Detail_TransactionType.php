<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Detail_TransactionType extends Model
{
    use HasFactory;
    protected $table = "dt_transaction_type";
    protected $primaryKey = "DT_TRANS_TY_ID";
}
