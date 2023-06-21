<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Detail_Structure;
use App\Models\Master\EmployeePosition;
use App\Models\Master\Structure as MasterStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Structure extends Controller
{
    //
    public function index_structure()
    {
        $result = [];
        try {
            /* Get grouped structure */
            $structure = MasterStructure::select('ms_structural.COMP_ID', 'ms_structural.DEPT_ID', 'ms_company.COMP_CODE', 'ms_company.COMP_NAME', 'ms_departement.DEPT_CODE', 'ms_departement.DEPT_NAME')
                ->leftJoin('ms_company', 'ms_structural.COMP_ID', '=', 'ms_company.COMP_ID')
                ->leftJoin('ms_departement', 'ms_structural.DEPT_ID', '=', 'ms_departement.DEPT_ID')
                ->where([['ms_company.STATUS', 0]])
                ->groupBy('ms_structural.COMP_ID', 'ms_structural.DEPT_ID')
                ->get();
            foreach ($structure as $keyStruct => $valStruct) {
                $res = [
                    "COMP_ID" => $valStruct->COMP_ID,
                    "DEPT_ID" => $valStruct->DEPT_ID,
                    "COMP_NAME" => '[' . $valStruct->COMP_CODE . '] ' . $valStruct->COMP_NAME,
                    "DEPT_NAME" => '[' . $valStruct->DEPT_CODE . '] ' . $valStruct->DEPT_NAME,
                    "STRUCTURE_COUNT" => 0,
                    "STRUCTURE_DETAIL" => []
                ];
                $detStructure = MasterStructure::select(
                    'ms_structural.STRUCTURE_ID',
                    'ms_structural.COMP_ID',
                    'ms_structural.DEPT_ID',
                    'ms_structural.STRUCTURE_NAME',
                    'ms_structural.STRUCTURE_DESCRIPTION',
                    'ms_structural.SYSTEM_DEFAULT',
                    'ms_structural.LOG_ACTIVITY',
                    'ms_company.COMP_CODE',
                    'ms_company.COMP_NAME',
                    'ms_departement.DEPT_CODE',
                    'ms_departement.DEPT_NAME',
                )
                    ->leftJoin('ms_company', 'ms_structural.COMP_ID', '=', 'ms_company.COMP_ID')
                    ->leftJoin('ms_departement', 'ms_structural.DEPT_ID', '=', 'ms_departement.DEPT_ID')
                    ->where([['ms_company.STATUS', 0], ['ms_structural.COMP_ID', $valStruct->COMP_ID], ['ms_structural.DEPT_ID', $valStruct->DEPT_ID]])
                    ->get();

                /* Structure counter */
                $res['STRUCTURE_COUNT'] = $detStructure->count();

                /* Structure detail */
                foreach ($detStructure as $keyDet => $detStruct) {
                    $resDet = [
                        "STRUCTURE_ID" => $detStruct->STRUCTURE_ID,
                        "COMP_ID" => $detStruct->COMP_ID,
                        "DEPT_ID" => $detStruct->DEPT_ID,
                        "STRUCTURE_NAME" => $detStruct->STRUCTURE_NAME,
                        "STRUCTURE_DESCRIPTION" => $detStruct->STRUCTURE_DESCRIPTION,
                        "SYSTEM_DEFAULT" => $detStruct->SYSTEM_DEFAULT,
                        "LOG_ACTIVITY" => $detStruct->LOG_ACTIVITY,
                        "COMP_CODE" => $detStruct->COMP_CODE,
                        "COMP_NAME" => $detStruct->COMP_NAME,
                        "DEPT_CODE" => $detStruct->DEPT_CODE,
                        "DEPT_NAME" => $detStruct->DEPT_NAME,
                        "COMP_DEPT" => "[" . $detStruct->COMP_CODE . "] " . $detStruct->DEPT_NAME
                    ];
                    array_push($res['STRUCTURE_DETAIL'], $resDet);
                }
                array_push($result, $res);
            }

            return response($result, 200);
        } catch (\Throwable $e) {
            /* Return Response on error */
            return response([
                "error" => $e->getMessage(),
                "message" => "Sorry, System can't receive your request",
            ], 500);
        }
    }

    public function get_structure(Request $request)
    {
        $result = [];
        /* Set first leader */
        $compId = $request->compId;
        $deptId = $request->deptId;
        $structureId = $request->structureId;

        /* Max leader position on structure */
        $firstLead = EmployeePosition::select(
            "dt_structural.LEADER_EMP_POST as LEADER_ID",
            "dt_structural.MEMBER_EMP_POST as MEMBER_ID",
            "ms_company.COMP_CODE",
            "ms_company.COMP_NAME",
            "ms_departement.DEPT_CODE",
            "ms_departement.DEPT_NAME",
            "ms_structural.STRUCTURE_NAME",
            "ms_structural.STRUCTURE_DESCRIPTION",
            "ms_structural.UPDATED_AT as STRUCTURE_UPDATE_AT",
            "fr_employee_position.UUID as EMP_POSITION_UUID",
            "ms_position.POST_NAME as EMP_POSITION",
            "ms_position.POST_NUMBER as EMP_POSITION_NUMBER",
            "fr_employee_position.ACTIVE as POSITION_STATUS",
            "ms_employee.EMPL_ID",
            "ms_employee.EMPL_NUMBER",
            "ms_employee.EMPL_UNIQUE_CODE",
            "ms_employee.EMPL_FIRSTNAME",
            "ms_employee.EMPL_LASTNAME",
            "ms_position.POST_APPROVAL_SET as APPROVAL_STATUS",
        )
            ->join('dt_structural', 'fr_employee_position.FR_POST_ID', '=', 'dt_structural.LEADER_EMP_POST')
            ->join('ms_structural', 'ms_structural.STRUCTURE_ID', '=', 'dt_structural.STRUCTURE_ID')
            ->join('ms_company', 'ms_structural.COMP_ID', '=', 'ms_company.COMP_ID')
            ->join('ms_departement', 'ms_structural.DEPT_ID', '=', 'ms_departement.DEPT_ID')
            ->join('ms_position', 'ms_position.POST_ID', '=', 'fr_employee_position.POST_ID')
            ->join('ms_employee', 'fr_employee_position.EMPL_ID', '=', 'ms_employee.EMPL_ID')
            ->whereRaw(
                'ms_position.POST_NUMBER = ( 
                    select max(MP.POST_NUMBER) 
                    from ms_position MP 
                    join fr_employee_position FP on mp.POST_ID = FP.POST_ID 
                    where FP.COMP_ID=' . $compId . ' and FP.DEPT_ID=' . $deptId . '
                )'
            )
            ->where([
                ['fr_employee_position.COMP_ID', $compId],
                ['fr_employee_position.DEPT_ID', $deptId],
                ['ms_structural.STRUCTURE_ID', $structureId],
            ])
            ->first();


        /* Get member structure */
        $getStructure = MasterStructure::select(
            "dt_structural.LEADER_EMP_POST as LEADER_ID",
            "dt_structural.MEMBER_EMP_POST as MEMBER_ID",
            "ms_company.COMP_CODE",
            "ms_company.COMP_NAME",
            "ms_departement.DEPT_CODE",
            "ms_departement.DEPT_NAME",
            "ms_structural.STRUCTURE_NAME",
            "ms_structural.STRUCTURE_DESCRIPTION",
            "ms_structural.UPDATED_AT as STRUCTURE_UPDATE_AT",
            "fr_employee_position.UUID as EMP_POSITION_UUID",
            "ms_position.POST_NAME as EMP_POSITION",
            "ms_position.POST_NUMBER as EMP_POSITION_NUMBER",
            "fr_employee_position.ACTIVE as POSITION_STATUS",
            "ms_employee.EMPL_ID",
            "ms_employee.EMPL_NUMBER",
            "ms_employee.EMPL_UNIQUE_CODE",
            "ms_employee.EMPL_FIRSTNAME",
            "ms_employee.EMPL_LASTNAME",
            "ms_position.POST_APPROVAL_SET as APPROVAL_STATUS",
            /* "ms_structural.STRUCTURE_ID",
            "ms_structural.COMP_ID",
            "ms_structural.DEPT_ID",
            "ms_structural.SYSTEM_DEFAULT",
            "ms_structural.STATUS",
            "fr_employee_position.FR_POST_ID",
            "fr_employee_position.START_DATE",
            "fr_employee_position.END_DATE",
            "fr_employee_position.ON_STRUCTURE",
            "ms_position.POST_ID",
            "ms_position.POST_STRUCTURE_LIMIT",
            "dt_structural.STRUCTURE_NUMBER", */
        )
            ->join('dt_structural', 'ms_structural.STRUCTURE_ID', '=', 'dt_structural.STRUCTURE_ID')
            ->join('ms_company', 'ms_structural.COMP_ID', '=', 'ms_company.COMP_ID')
            ->join('ms_departement', 'ms_structural.DEPT_ID', '=', 'ms_departement.DEPT_ID')
            ->join('fr_employee_position', 'dt_structural.MEMBER_EMP_POST', '=', 'fr_employee_position.FR_POST_ID')
            ->join('ms_position', 'ms_position.POST_ID', '=', 'fr_employee_position.POST_ID')
            ->join('ms_employee', 'fr_employee_position.EMPL_ID', '=', 'ms_employee.EMPL_ID')
            ->where([
                ['fr_employee_position.COMP_ID', $compId],
                ['fr_employee_position.DEPT_ID', $deptId],
                ['ms_structural.STRUCTURE_ID', $structureId],
                ['dt_structural.ACTIVE', 1],
            ])
            ->orderBy('dt_structural.LEADER_EMP_POST', 'desc')
            ->get();

        /* Set structure */
        $a = 1;
        foreach ($getStructure as $key => $value) {
            $setStruct = [
                "nodeIds" => strval($a),
                "label" => "[" . $value->EMP_POSITION . "] " . $value->EMPL_FIRSTNAME . " " . $value->EMPL_LASTNAME,
                "leader_id" => $value->LEADER_ID,
                "member_id" => $value->MEMBER_ID,
            ];
            array_push($result, $setStruct);
            $a++;
        }

        /* Create array tree */
        $leadTree = [];
        if (@$firstLead->LEADER_ID) {
            $buildRes = $this->buildTree($result, $firstLead->LEADER_ID);
            $leadTree = [
                "nodeIds" => "0",
                "label" => "[" . $firstLead->EMP_POSITION . "] " . $firstLead->EMPL_FIRSTNAME . " " . $firstLead->EMPL_LASTNAME,
                "leader_id" => $firstLead->LEADER_ID,
            ];
            $leadTree['childs'] = $buildRes;
        }

        /* Give FeedBack */
        return response([$leadTree], 200);
    }

    private function buildTree($elements, $firstLeader, array $leadData = [])
    {
        $branch = array();
        foreach ($elements as $element) {
            if ($element['leader_id'] == $firstLeader) {
                $children = $this->buildTree($elements, $element['member_id']);
                if ($children) {
                    $element['childs'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    public function create_msStructure(Request $request)
    {
        // Validation
        $validateUser = Validator::make(
            $request->all(),
            [
                "departement" => ['required'],
                "company" => ['required'],
                // 'company' => [
                //     Rule::unique('ms_structural', "COMP_ID")->where(function ($query) use ($request) {
                //         return $query
                //             ->where('DEPT_ID', $request->departement);
                //     }),
                // ],
                "structureName" => ['required'],
                "description" => ['required'],
            ],
            [
                'company.unique' => "The company with this departement is exist",
            ],
        );

        if ($validateUser->fails()) {
            return response([
                'status' => false,
                'message' => 'User Validation Error',
                'errors' => $validateUser->errors()
            ], 400);
        }

        /* Crete Company -> Departement */
        try {
            DB::transaction(function () use ($request) {
                MasterStructure::create([
                    "COMP_ID" => $request->company,
                    "DEPT_ID" => $request->departement,
                    "STRUCTURE_NAME" => $request->structureName,
                    "STRUCTURE_DESCRIPTION" => $request->description,
                    // "SYSTEM_DEFAULT" => $request->default,
                ]);
                return response([], 200);
            });
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            /* Return Response on error */
            return response([
                "error" => $e->getMessage(),
                "message" => "Sorry, System can't receive your request",
            ], 500);
        }
    }
    public function create_dtStructure(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $structure = $request->employeeStructure;
                foreach ($structure as $keyStruct => $valStruct) {
                    Detail_Structure::create([
                        "MEMBER_EMP_POST" => $valStruct['memberId'],
                        "LEADER_EMP_POST" => $valStruct['leaderId'],
                        "STRUCTURE_ID" => $request->structureId,
                        "STRUCTURE_NUMBER" => (int) $valStruct['nodeIds'],
                        "APPROVE_LOWER" => ($valStruct['leaderExceptAmount']['lower'] != "" || $valStruct['leaderExceptAmount']['lower'] != 0) ? 0 : 1,
                        "APPROVE_LOWER_THAN" => ($valStruct['leaderExceptAmount']['lower'] == "") ? 0 :
                            $valStruct['leaderExceptAmount']['lower'],
                        "APPROVE_UPPER" => ($valStruct['leaderExceptAmount']['upper'] != "" || $valStruct['leaderExceptAmount']['upper'] != 0) ? 0 : 1,
                        "APPROVE_UPPER_THAN" => ($valStruct['leaderExceptAmount']['upper'] == "") ? 0 :
                            $valStruct['leaderExceptAmount']['upper'],
                    ]);
                }
            });
            DB::commit();
            return response([], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            /* Return Response on error */
            return response([
                "error" => $e->getMessage(),
                "message" => "Sorry, System can't receive your request",
            ], 500);
        }
        return response($request);
    }
}
