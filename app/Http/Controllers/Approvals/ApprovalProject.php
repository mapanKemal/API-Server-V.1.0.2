<?php

namespace App\Http\Controllers\Approvals;

use App\Http\Controllers\Controller;
use App\Models\Master\Company;
use App\Models\Master\Departement;
use App\Models\Master\Detail_Structure;
use App\Models\Master\EmployeePosition;
use App\Models\Master\Structure;
use Illuminate\Http\Request;

class ApprovalProject extends Controller
{
    //
    protected $advance_transTyId = 2, $reimburse_transTyId = 9, $general_transTyId = 16;

    public function createApproval(Request $request, string $uuid)
    {
        $_company = Company::select("COMP_ID")->where([['COMP_CODE', $request->companyCode]])->firstOrFail();
        $_departement = Departement::select("DEPT_ID")->where([['DEPT_CODE', $request->deptartementCode]])->firstOrFail();
        /* Get Structure Division */
        $mStructure = Structure::where([
            ["COMP_ID", $_company->COMP_ID],
            ["DEPT_ID", $_departement->DEPT_ID],
            ["SYSTEM_DEFAULT", 0],
            ["STATUS", 0]
        ])->firstOrFail();
        /* Employee Position */
        $empPosition = EmployeePosition::where([
            ["COMP_ID", $_company->COMP_ID],
            ["DEPT_ID", $_departement->DEPT_ID],
            ["EMPL_ID", $request->employeeId],
            ["ACTIVE", 1],
            // ["USER_ID", $_departement->DEPT_ID],
        ])->firstOrFail();

        $structOnApproval = [];
        /* Dept Default Structure */
        array_push($structOnApproval, $mStructure->STRUCTURE_ID);
        $dtStructureEmp = $this->empDetailStructure($mStructure->STRUCTURE_ID);
        $divStructure = array_merge($this->getSructure($dtStructureEmp, $empPosition->FR_POST_ID), [$empPosition->FR_POST_ID]);
        krsort($divStructure);

        /* System Default Structure By Transaction Type */
        $transTypeStructure = [];

        return response($transTypeStructure);
    }

    private function empDetailStructure($structureId)
    {
        /* Get Detail Structure */
        return Detail_Structure::select(
            "MEMBER_EMP_POST",
            "LEADER_EMP_POST",
        )
            ->where([
                ["STRUCTURE_ID", $structureId],
            ])->get();
    }
    private function getSructure($elements, $firstLead)
    {
        $rest = [];
        foreach ($elements as $element) {
            if ($element->MEMBER_EMP_POST == $firstLead) {
                $children = $this->getSructure($elements, $element->LEADER_EMP_POST);
                if ($children) {
                    $rest = $children;
                }
                $rest[] = $element->LEADER_EMP_POST;
            }
        }
        return $rest;
    }
    private function setApproval(Request $request, string $uuid)
    {
    }
}
