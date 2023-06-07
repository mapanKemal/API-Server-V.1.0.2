<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Employee as MasterEmployee;
use Illuminate\Http\Request;

class Employee extends Controller
{

    /* Employee Setup */
    public function index_Employee()
    {
        $post = MasterEmployee::where('STATUS', 0)->get();
        return response($post);
    }
    public function create_Employee()
    {
    }
    public function update_Employee()
    {
    }
    public function delete_Employee()
    {
    }
    /* Employee Setup */
}
