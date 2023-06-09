<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Company;
use App\Models\Master\Departement;
use App\Models\Master\JobPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Setup_Company extends Controller
{
    /* Company Setup */
    public function index_Company()
    {
        // $result = [];
        $comp = Company::where('STATUS', 0)->get();
        return response($comp);
    }
    public function create_Company(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'companyCode' => ['required', Rule::unique('ms_company', 'COMP_CODE'), 'max:3', 'min:2'],
                    'companyName' => ['required']
                ]
            );

            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please complete the form',
                    'errors' => $validateUser->errors()
                ], 500);
            }

            $company = Company::create([
                "COMP_CODE" => $request->companyCode,
                "COMP_NAME" => $request->companyName,
            ]);
            return response([
                'status' => true,
                'message' => 'Created Successfully',
                'data'  => [
                    'COMP_CODE' => $company->COMP_CODE,
                    'COMP_NAME' => $company->COMP_NAME
                ],
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    public function update_Company()
    {
    }
    public function delete_Company()
    {
    }
    public function option_Company()
    {
        $result = [];
        $comp = Company::where('STATUS', 0)->get();
        foreach ($comp as $keyComp => $valComp) {
            $res = [
                "value" => $valComp->COMP_ID,
                "label" => "[" . $valComp->COMP_CODE . "] " . $valComp->COMP_NAME,
            ];
            array_push($result, $res);
        }
        return response($result);
    }
    /* Company Setup */

    /* Departement Setup */
    public function index_Departement()
    {
        $dept = Departement::where('STATUS', 0)->get();
        return response($dept);
    }
    public function create_Departement(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'departementCode' => ['required', Rule::unique('ms_departement', 'DEPT_CODE'), 'max:3', 'min:2'],
                    'departementName' => ['required']
                ]
            );

            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please complete the form',
                    'errors' => $validateUser->errors()
                ], 500);
            }

            $departement = Departement::create([
                "DEPT_CODE" => $request->departementCode,
                "DEPT_NAME" => $request->departementName,
            ]);
            return response([
                'status' => true,
                'message' => 'Created Successfully',
                'data'  => [
                    'DEPT_CODE' => $departement->DEPT_CODE,
                    'DEPT_NAME' => $departement->DEPT_NAME
                ],
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    public function update_Departement()
    {
    }
    public function delete_Departement()
    {
    }
    public function option_Departement()
    {
        $result = [];
        $dept = Departement::where('STATUS', 0)->get();
        foreach ($dept as $keyDept => $valDept) {
            $res = [
                "value" => $valDept->DEPT_ID,
                "label" => "[" . $valDept->DEPT_CODE . "] " . $valDept->DEPT_NAME,
            ];
            array_push($result, $res);
        }
        return response($result);
    }
    /* Departement Setup */

    /* Job Position Setup */
    public function index_JobPosition()
    {
        $post = JobPosition::where('STATUS', 0)->get();
        return response($post);
    }
    public function create_JobPosition()
    {
    }
    public function update_JobPosition()
    {
    }
    public function delete_JobPosition()
    {
    }
    public function option_JobPosition()
    {
        $result = [];
        $post = JobPosition::where('STATUS', 0)->get();
        foreach ($post as $keyPost => $valPost) {
            $res = [
                "value" => $valPost->POST_ID,
                "label" => $valPost->POST_NAME,
            ];
            array_push($result, $res);
        }
        return response($result);
    }
    /* Job Position Setup */
}
