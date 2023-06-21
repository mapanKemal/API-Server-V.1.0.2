<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Employee as MasterEmployee;
use App\Models\Master\EmployeePosition;
use App\Models\User;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
        // Validation
        $validateUser = Validator::make(
            $request->all(),
            [
                'username' => ['required', Rule::unique('ms_users', 'USERNAME'), 'min:2'],
                'password' => ['required', 'min:3', 'max:10'],
                'eployeeNumber' => ['required', Rule::unique('ms_employee', 'EMPL_UNIQUE_CODE'), 'min:1'],
                'company' => ['required'],
                'departement' => ['required'],
                'position' => ['required'],
            ]
        );

        if ($validateUser->fails()) {
            return response([
                'status' => false,
                'message' => 'User Validation Error',
                'errors' => $validateUser->errors()
            ], 400);
        }

        try {
            DB::transaction(function () use ($request) {
                /* Create User  */
                $user = User::create([
                    "ALIASES" => $request->aliases,
                    "USERNAME" => $request->username,
                    "PASSWORD" => Hash::make($request->password),
                    "EMAIL" => $request->email,
                ]);
                /* Create Employee  */
                $empNumbConf = [
                    'table' => 'ms_employee',
                    'field' => 'EMPL_NUMBER',
                    'length' => 16,
                    'prefix' =>  "EMPL-" . date("Ymd"),
                    'reset_on_prefix_change' => true,

                ];
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
                EmployeePosition::create([
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
            });
            DB::commit();
            /* Return Response on success */
            return response([], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            /* Return Response on error */
            return response([
                "error" => $e->getMessage(),
                "message" => "Sorry, System can't receive your request",
            ], 500);
        }
    }
    public function employee_onPosition(Request $request)
    {
        $result = [];
        $empPosition = EmployeePosition::select(
            "fr_employee_position.FR_POST_ID",
            "ms_employee.EMPL_FIRSTNAME",
            "ms_employee.EMPL_LASTNAME",
            "ms_employee.STATUS",
            "ms_position.POST_NAME",
        )
            ->join('ms_employee', 'fr_employee_position.EMPL_ID', '=', 'ms_employee.EMPL_ID')
            ->join('ms_position', 'fr_employee_position.POST_ID', '=', 'ms_position.POST_ID')
            ->where([
                ['ms_employee.STATUS', 0],
                ['fr_employee_position.COMP_ID', $request->compId],
                ['fr_employee_position.DEPT_ID', $request->deptId]
            ])
            ->get();
        foreach ($empPosition as $keyEmpPost => $valEmpPost) {
            $res = [
                "value" => $valEmpPost->FR_POST_ID,
                "label" => "[" . $valEmpPost->POST_NAME . "]" . $valEmpPost->EMPL_FIRSTNAME . ' ' . $valEmpPost->EMPL_LASTNAME
            ];

            array_push($result, $res);
        }
        return response($result);
    }
    public function update_Employee()
    {
    }
    public function delete_Employee()
    {
    }
    /* Employee Setup */
}
