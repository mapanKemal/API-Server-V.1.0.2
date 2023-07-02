<?php

namespace App\Http\Controllers\Approvals;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Transaction\Project as TransactionProject;
use App\Models\Approvals\Approval;
use App\Models\Approvals\Detail_Approval;
use App\Models\Approvals\Structure_Approval;
use App\Models\Master\EmployeePosition;
use App\Models\Master\Structure;
use App\Models\Transaction\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalProject extends Controller
{
    //
    protected $advance_transTyId = 2, $reimburse_transTyId = 9, $general_transTyId = 16;

    public function createApproval(Request $request, string $uuid)
    {
        /* Update Project Transaction */
        $transProject = new TransactionProject;
        $setTransProject = $transProject->createFromApproval($request, $uuid);
        if (!$setTransProject) {
            return response($setTransProject, 500);
        }

        /* Project Check */
        $_Project = Project::where([
            ['UUID', $uuid]
        ])->firstOrFail();
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

        /** 
         * Create approval data
         */
        $approval = new ApprovalBase;

        try {
            DB::transaction(function () use (
                $request,
                $uuid,
                $_Project,
                $approval,
            ) {

                $divStructure = $this->get_DivisionStructure($approval, $_Project, $request->userId);
                $approvalSet = $approval->setApproval($divStructure, $request->transactionType);

                /* TR Approval Log Set */
                $logTrTable = [
                    [
                        "Time" => date('Y-m-d H:i:s'),
                        "User"   => "System",
                        "RequestDate"   => null,
                        "Subject" => "Approval structure created",
                    ]
                ];
                /* Create master transaction approval */
                $tr_approval = Approval::create([
                    "COMP_ID" => $_Project->COMP_ID,
                    "DEPT_ID" => $_Project->DEPT_ID,
                    // "STATUS",
                    // "APPR_FINAL_DATE",
                    // "APPR_DEACTIVE",
                    // "APPR_DEACTIVE_DATE",
                    // "APPR_DEACTIVE_REASON",
                    "LOG_ACTIVITY" => $logTrTable,
                ]);

                /* Creaye detail transaction approval */
                $dtNumber = 0;
                foreach ($approvalSet['structure'] as $keyClStructure => $valClStructure) {
                    $dtNumber = $dtNumber + 1;
                    /* Get FR Employee position */
                    $getFr = EmployeePosition::select(
                        "USER_ID",
                        "EMPL_ID",
                    )->where([
                        ['FR_POST_ID', $valClStructure]
                    ])->firstOrFail();

                    /* Save log to master transaction approval */
                    if ($keyClStructure == 0 || $keyClStructure == 1) {
                        $tr_approval->LOG_ACTIVITY->append((object) [
                            "time" => date('Y-m-d H:i:s'),
                            "user" => $getFr->USER_ID,
                            "requestDate" => $keyClStructure == 1 ? date('Y-m-d H:i:s') : null,
                            "subject" => $keyClStructure == 0 ? "New request approval" : ($keyClStructure == 1 ? "Approval request send" : null),
                        ]);
                        $tr_approval->save();
                    }

                    /* Create detail transaction approval */
                    $dt_approval = Detail_Approval::create([
                        "USER_ID" => $getFr->USER_ID,
                        "EMPL_ID" => $getFr->EMPL_ID,
                        "APPROVAL_ID" => $tr_approval->APPROVAL_ID,
                        "APPROVAL_CODE_ID" => $keyClStructure == 0 ? 2 : ($keyClStructure == 1 ? 3 : 1), # New approval code
                        "DT_APPR_REQ_DATE" => $keyClStructure == 0 ? date('Y-m-d H:i:s') : ($keyClStructure == 1 ? date('Y-m-d H:i:s') : null),
                        "DT_APPR_NUMBER" => $dtNumber,
                        "LOG_ACTIVITY" => [],
                        // "DT_APPR_DATE",
                        // "DT_APPR_DESCRIPTION",
                        // "DT_APPR_DEACTIVE",
                        // "STATUS",
                        // "NOTIFICATION_RESPONSE",
                    ]);

                    /* Log on detail table */
                    $dt_approval->LOG_ACTIVITY = [
                        "time" => date('Y-m-d H:i:s'),
                        "user" => $keyClStructure == 0 ? $getFr->USER_ID : ($keyClStructure == 1 ? $getFr->USER_ID : "System"),
                        "subject" => $keyClStructure == 0 ? "Created request" : ($keyClStructure == 1 ? "Waiting approval" : "Approval structure created"),
                        "desc" => [
                            "USER_ID" => $getFr->USER_ID,
                            "EMPL_ID" => $getFr->EMPL_ID,
                            "APPROVAL_CODE_ID" => $keyClStructure == 0 ? 2 : ($keyClStructure == 1 ? 3 : 1),
                            "DT_APPR_REQ_DATE" => $keyClStructure == 0 ? date('Y-m-d H:i:s') : ($keyClStructure == 1 ? date('Y-m-d H:i:s') : null),
                        ]
                    ];
                    $dt_approval->save();

                    /* Send mail notification */
                    # Code...
                }

                /* Set Structure On Approval */
                $sequence = 0;
                foreach ($approvalSet['structureOnApproval'] as $keyStructId => $valStructId) {
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

                /* Set approval for detail project transaction */
                # Code...

                /* Done project approval */
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

    private function get_DivisionStructure($approvalBase, $transaction, $userId)
    {
        /* Get master structure */
        $mStructure = Structure::where([
            ["COMP_ID", $transaction->COMP_ID],
            ["DEPT_ID", $transaction->DEPT_ID],
            ["SYSTEM_DEFAULT", 0],
            ["STATUS", 0]
        ])->firstOrFail();
        array_push($approvalBase->__structure['structureOnApproval'], $mStructure->STRUCTURE_ID);

        /* Employee Position */
        $employeePosition = EmployeePosition::where([
            ["COMP_ID", $transaction->COMP_ID],
            ["DEPT_ID", $transaction->DEPT_ID],
            ["USER_ID", $userId],
            // ["EMPL_ID", $request->employeeId],
            ["ACTIVE", 1],
        ])->firstOrFail();

        /* Set used structure */

        /* Set used structure */
        $structureDetail = $approvalBase->get_StructureDetail($mStructure->STRUCTURE_ID, $transaction->PRJ_TOTAL_AMOUNT_REQUEST);
        $structure = array_merge($approvalBase->make_StructureTree($structureDetail, $employeePosition->FR_POST_ID), [$employeePosition->FR_POST_ID]);
        krsort($structure);

        return $structure;
    }
}
