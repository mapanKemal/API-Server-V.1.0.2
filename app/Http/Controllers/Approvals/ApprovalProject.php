<?php

namespace App\Http\Controllers\Approvals;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Transaction\Project as TransactionProject;
use App\Models\Approvals\Approval;
use App\Models\Approvals\Detail_Approval;
use App\Models\Approvals\Structure_Approval;
use App\Models\Approvals\SystemStructural;
use App\Models\Master\Company;
use App\Models\Master\Departement;
use App\Models\Master\Detail_Structure;
use App\Models\Master\EmployeePosition;
use App\Models\Master\Structure;
use App\Models\Transaction\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalProject extends Controller
{
    //
    protected $advance_transTyId = 2, $reimburse_transTyId = 9, $general_transTyId = 16;
    protected $transType = [
        "project" => 1,
        "cash_advanced" => 2,
        "reimbursement" => 9,
        "general" => 16,
    ];

    public function createApproval(Request $request, string $uuid)
    {
        $transProject = new TransactionProject;
        $setTransProject = $transProject->createFromApproval($request, $uuid);
        if (!$setTransProject) {
            return response($setTransProject, 500);
        }
        /* Project Check */
        $_Project = Project::where([['UUID', $uuid]])->firstOrFail();
        if (
            $_Project->PRJ_TOTAL_AMOUNT_REQUEST === null ||
            $_Project->PRJ_TOTAL_AMOUNT_REQUEST === 0 ||
            $_Project->PRJ_TOTAL_AMOUNT_USED === null ||
            $_Project->PRJ_TOTAL_AMOUNT_USED === 0
        ) {
            return response([
                "message" => "Please complete your request",
            ], 500);
        } elseif ($_Project->APPROVAL_ID !== null) {
            return response([
                "message" => "This transaction on approval progress",
            ], 500);
        }

        /* Get Data Company Dept */
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
            ["USER_ID", $request->userId],
            // ["EMPL_ID", $request->employeeId],
            ["ACTIVE", 1],
        ])->firstOrFail();

        $structOnApproval = [];
        /* Push structure on approval */
        array_push($structOnApproval, $mStructure->STRUCTURE_ID);
        /* Dept Default Structure */
        $dtStructureEmp = $this->empDetailStructure($mStructure->STRUCTURE_ID);
        $divStructure = array_merge($this->getSructure($dtStructureEmp, $empPosition->FR_POST_ID), [$empPosition->FR_POST_ID]);
        krsort($divStructure);

        /* System Default Structure By Transaction Type */
        $transTypeStructure = [];
        $_defaultStructure = SystemStructural::select("STRUCTURE_ID")
            ->where([
                ['TRANS_TY_ID', $request->transactionType], # Search structure ID By Transaction Type 
                ['ACTIVE', 1]
            ])
            ->orderBy('STURCT_APP_SEQUENCE', 'desc')
            ->get();
        foreach ($_defaultStructure as $keyDfStruct => $valDfStruct) {
            /* Get leads on structure */
            $setLead = Detail_Structure::select(
                "MEMBER_EMP_POST"
            )
                ->where([
                    ["STRUCTURE_ID", $valDfStruct->STRUCTURE_ID],
                    ["ACTIVE", 1],
                ])
                ->orderBy('STRUCTURE_NUMBER', 'asc')
                ->firstOrFail();
            /* Push structure on approval */
            array_push($structOnApproval, $valDfStruct->STRUCTURE_ID);
            $_df_getDtStructure = $this->empDetailStructure($valDfStruct->STRUCTURE_ID);

            /* System Dept Default Structure By Trans Type*/
            $_df_divStructure = array_merge($this->getSructure($_df_getDtStructure, $setLead->MEMBER_EMP_POST), [$setLead->MEMBER_EMP_POST]);
            krsort($_df_divStructure);
            array_push($transTypeStructure, $_df_divStructure);
        }

        /**
         * PopOut Duplicate Keys 
         * By Smaller Keys
         * */
        $mgrApprovalStructure = array_merge($divStructure, ...$transTypeStructure);
        /* Get Duplicate */
        $_getDuplicate = $this->getDuplicateArray($mgrApprovalStructure);
        /* Clean structure */
        $cleanStructure = $this->cleanDuplicateArray($_getDuplicate, $mgrApprovalStructure);

        try {
            DB::transaction(function () use (
                $request,
                $uuid,
                $_company,
                $_departement,
                $_Project,
                $cleanStructure,
                $structOnApproval,
            ) {
                /* Set TR Approval */
                $tr_approval = Approval::create([
                    "COMP_ID" => $_company->COMP_ID,
                    "DEPT_ID" => $_departement->DEPT_ID,
                    // "STATUS",
                    // "APPR_FINAL_DATE",
                    // "APPR_DEACTIVE",
                    // "APPR_DEACTIVE_DATE",
                    // "APPR_DEACTIVE_REASON",
                    // "LOG_ACTIVITY",
                ]);

                $dtNumber = 0;
                foreach ($cleanStructure as $keyClStructure => $valClStructure) {
                    $dtNumber = $dtNumber + 1;
                    $getFr = EmployeePosition::select(
                        "USER_ID",
                        "EMPL_ID",
                    )->where([
                        ['FR_POST_ID', $valClStructure]
                    ])->firstOrFail();
                    /* Create Detail Approval */
                    Detail_Approval::create([
                        "USER_ID" => $getFr->USER_ID,
                        "EMPL_ID" => $getFr->EMPL_ID,
                        "APPROVAL_ID" => $tr_approval->APPROVAL_ID,
                        "APPROVAL_CODE_ID" => $keyClStructure == 0 ? 2 : ($keyClStructure == 1 ? 3 : 1), # New approval code
                        "DT_APPR_REQ_DATE" => $keyClStructure == 0 ? date('Y-m-d H:i:s') : ($keyClStructure == 1 ? date('Y-m-d H:i:s') : null),
                        "DT_APPR_NUMBER" => $dtNumber,
                        // "DT_APPR_DATE",
                        // "DT_APPR_DESCRIPTION",
                        // "DT_APPR_DEACTIVE",
                        // "STATUS",
                        // "NOTIFICATION_RESPONSE",
                    ]);
                }

                /* Set Structure On Approval */
                $sequence = 0;
                foreach ($structOnApproval as $keyStructId => $valStructId) {
                    $sequence = $sequence + 1;
                    Structure_Approval::create([
                        "STRUCTURE_ID" => $valStructId,
                        "APPROVAL_ID" => $tr_approval->APPROVAL_ID,
                        "STURCT_APP_SEQUENCE" => $sequence,
                    ]);
                }

                /* Update Project Data */
                $_Project->APPROVAL_ID = $tr_approval->APPROVAL_ID;
                $_Project->STATUS = 1; # On progress status
                $_Project->save();
            });
            DB::commit();
            return response($_Project, 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            /* Return Response on error */
            return response([
                "error" => $e->getMessage(),
                "message" => "Sorry, System can't receive your request",
            ], 500);
        }
    }

    private function cleanDuplicateArray($duplicates = [], $arrayData)
    {
        /* Clean array */
        foreach ($duplicates as $key => $value) {
            if (count($arrayData) > 1) {
                $keys = array_keys($arrayData, $value);
                unset($arrayData[$keys[0]]);

                $counts = $this->getDuplicateArray($arrayData);
                if (count($counts) == 1) {
                    $arrayData = $this->cleanDuplicateArray($counts, $arrayData);
                }
            }
        }
        return $arrayData;
    }
    private function getDuplicateArray($arrayData)
    {
        $counts = array_count_values($arrayData);
        // Initialize an empty array to store duplicate values
        $duplicates = [];

        // Loop through the counts and check for values occurring more than once
        foreach ($counts as $value => $count) {
            if ($count > 1) {
                $duplicates[] = $value;
            }
        }
        return $duplicates;
    }

    private function empDetailStructure($structureId)
    {
        /* Get Detail Structure */
        return Detail_Structure::select(
            "MEMBER_EMP_POST",
            "LEADER_EMP_POST",
        )->where([
            ["STRUCTURE_ID", $structureId],
            ["ACTIVE", 1],
        ])->orderBy('STRUCTURE_NUMBER', 'asc')->get();
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
}
