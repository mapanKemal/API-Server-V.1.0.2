<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Employee as MasterEmployee;
use App\Models\Master\EmployeePosition;
use App\Models\User;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Http\Request;

class Employee extends Controller
{

    /* Employee Setup */
    public function index_Employee()
    {
        $post = MasterEmployee::where('STATUS', 0)->get();
        return response($post);
    }
    public function create_Employee(Request $request)
    {
        /* Create User  */
        $user = User::create([
            "ALIASES" => $request->aliases,
            "USERNAME" => $request->username,
            "PASSWORD" => $request->password,
            "EMAIL" => $request->email,
        ]);
        /* Create Employee  */
        $empNumbConf = [
            'table' => 'ms_employee',
            'field' => 'EMPL_NUMBER',
            'length' => 16,
            'prefix' =>  "EMPL-" . date("Ymd")
        ];;
        $empNumber = IdGenerator::generate($empNumbConf);
        $employee = MasterEmployee::create([
            "EMPL_NUMBER" => $empNumber,
            "USER_ID" => $user->USER_ID,
            "EMPL_UNIQUE_CODE" => $request->eployeeNumber,
            "EMPL_FIRSTNAME" => $request->firstName,
            "EMPL_LASTNAME" => $request->lastName,
            "EMPL_GENDER" => $request->gender
        ]);
        /* Create FR-Employee  */
        $employeePosition = EmployeePosition::create([
            "USER_ID" => $user->USER_ID,
            "EMPL_ID" => $employee->EMPL_ID,
            "COMP_ID" => $request->company,
            "DEPT_ID" => $request->departement,
            "POST_ID" => $request->position,
            "ACCP_LOWER" => 0,
            "ACCP_LOWER_AMOUNT" => 0,
            "ACCP_UPPER" => 0,
            "ACCP_UPPER_AMOUNT" => 0,
            "ON_STRUCTURE" => 0,
        ]);
        return response([
            "user" => $user,
            "employee" => $employee,
            "position" => $employeePosition,
        ]);
    }
    public function update_Employee()
    {
    }
    public function delete_Employee()
    {
    }
    /* Employee Setup */
}
