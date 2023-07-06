<?php

namespace App\Http\Controllers\Approvals;

use App\Http\Controllers\Controller;
use App\Models\Approvals\Approval;
use App\Models\Approvals\SystemStructural;
use App\Models\Master\ApprovalCode;
use App\Models\Master\Detail_Structure;
use Illuminate\Http\Request;

class ApprovalBase extends Controller
{
    private $transStructureBytype = [];
    /* Default Approval Set */
    public $__structure = [
        "structureOnApproval" => [],
    ];
    public function _apprCodeToStatus($key)
    {
        $apprStatus = [
            2 => 0,
            1 => 1,
            3 => 1,
            5 => 1,
            7 => 1,
            8 => 1,
            15 => 1,
            4 => 96,
            6 => 96,
            9 => 96,
            10 => 96,
            11 => 98,
            12 => 96,
            14 => 98,
            13 => 99,
        ];
        return $apprStatus[$key];
    }
    public function _apprCode()
    {
        return response(ApprovalCode::select('APPROVAL_CODE_DESC')->orderBy('APPROVAL_CODE_DESC', 'asc')->get());
    }
    public function setApproval($divStructure, $transactionType, $cleanDuplicateOpt = [])
    {
        /* Set Structure By Transaction Type */
        $this->setSystemStructure($transactionType);
        print_r($this->transStructureBytype);

        /* Merger Structure */
        $mrgStructure = array_merge($divStructure, ...$this->transStructureBytype);

        /* Get Duplicate & Clean It!!! */
        $this->__structure['structure'] = $this->clean_StructureDuplicate(
            $this->get_StructureDuplicate($mrgStructure),
            $mrgStructure,
            $cleanDuplicateOpt
        );
        return $this->__structure;
    }
    public function get_StructureDetail($structureId, $transactionAmount = 0)
    {
        /* Get Detail Structure */
        $structure = [];
        $structureDetail = Detail_Structure::select(
            "MEMBER_EMP_POST",
            "LEADER_EMP_POST",
            "APPROVE_LOWER_THAN",
            "APPROVE_UPPER_THAN",
            "APPROVE_LOWER",
            "APPROVE_UPPER",
        )->where([
            ["STRUCTURE_ID", $structureId],
            ["ACTIVE", 1],
        ])->orderBy('STRUCTURE_NUMBER', 'asc')->get();
        foreach ($structureDetail as $keyStrDetail => $valStrDetail) {
            if (
                $transactionAmount != 0 &&
                $valStrDetail->APPROVE_LOWER == 1 &&
                $valStrDetail->APPROVE_UPPER == 1 &&
                $transactionAmount < $valStrDetail->APPROVE_LOWER_THAN &&
                $transactionAmount >= $valStrDetail->APPROVE_UPPER_THAN #When lower and upper true
            ) {
                array_push($structure, $valStrDetail);
            } elseif (
                $transactionAmount != 0 &&
                $valStrDetail->APPROVE_LOWER == 1 &&
                $transactionAmount < $valStrDetail->APPROVE_LOWER_THAN
            ) {
                array_push($structure, $valStrDetail);
            } elseif (
                $transactionAmount != 0 &&
                $valStrDetail->APPROVE_UPPER == 1 &&
                $transactionAmount >= $valStrDetail->APPROVE_UPPER_THAN
            ) {
                array_push($structure, $valStrDetail);
            } elseif (
                $transactionAmount != 0 &&
                $valStrDetail->APPROVE_LOWER == 0 &&
                $valStrDetail->APPROVE_UPPER == 0
            ) {
                array_push($structure, $valStrDetail);
            } elseif (
                $transactionAmount == 0 &&
                $valStrDetail->APPROVE_LOWER == 0 &&
                $valStrDetail->APPROVE_UPPER == 0
            ) {
                array_push($structure, $valStrDetail);
            }
        }
        return $structure;
    }
    public function make_StructureTree($structure, $firstLead)
    {
        $result = [];
        foreach ($structure as $element) {
            if ($element->MEMBER_EMP_POST == $firstLead) {
                $children = $this->make_StructureTree($structure, $element->LEADER_EMP_POST);
                if ($children) {
                    $result = $children;
                }
                $result[] = $element->LEADER_EMP_POST;
            }
        }
        return $result;
    }
    public function nextApproval($lastApprNumber, $approvalId, $compId, $deptId)
    {
        return Approval::select(
            "ms_employee.EMPL_FIRSTNAME",
            "ms_employee.EMPL_LASTNAME",
            "ms_users.ALIASES",
            "dt_approval.UUID",
            // "dt_approval.DT_APPR_ID",
        )
            ->join('dt_approval', 'tr_approval.APPROVAL_ID', '=', 'dt_approval.APPROVAL_ID')
            ->join('ms_users', 'dt_approval.USER_ID', '=', 'ms_users.USER_ID')
            ->join('ms_employee', 'dt_approval.EMPL_ID', '=', 'ms_employee.EMPL_ID')
            ->where([
                ['tr_approval.APPROVAL_ID', $approvalId],
                ['tr_approval.COMP_ID', $compId],
                ['tr_approval.DEPT_ID', $deptId],
                ['dt_approval.DT_APPR_NUMBER', ($lastApprNumber + 1)],
                ['dt_approval.APPROVAL_CODE_ID', 1],
            ])->firstOrFail();
    }

    private function setSystemStructure($transactionType)
    {
        $mStructure = SystemStructural::select("STRUCTURE_ID")
            ->where([
                ['TRANS_TY_ID', $transactionType], # Search structure ID By Transaction Type 
                ['ACTIVE', 1]
            ])
            ->orderBy('STURCT_APP_SEQUENCE', 'desc')
            ->get();
        foreach ($mStructure as $keyStructure => $structures) {
            $setLead = Detail_Structure::select(
                "MEMBER_EMP_POST"
            )
                ->where([
                    ["STRUCTURE_ID", $structures->STRUCTURE_ID],
                    ["ACTIVE", 1],
                ])
                ->orderBy('STRUCTURE_NUMBER', 'asc')
                ->firstOrFail();

            /* Set used structure */
            array_push($this->__structure['structureOnApproval'], $structures->STRUCTURE_ID);

            /* Set used structure */
            $structureDetail = $this->get_StructureDetail($structures->STRUCTURE_ID);
            $structure = array_merge($this->make_StructureTree($structureDetail, $setLead->MEMBER_EMP_POST), [$setLead->MEMBER_EMP_POST]);
            krsort($structure);

            array_push($this->transStructureBytype, $structure);
        }
    }
    private function get_StructureDuplicate($arrayData)
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
    /**
     * The attributes that should be hidden for serialization.
     *
     * @param $option[
     *  exceptionVal => array() # Except value when you need...
     * 
     *  custom => [[value, unsetKey]] # Use 2 dimension array on unsetKey, Defaut key is 0
     * 
     * ]
     */
    private function clean_StructureDuplicate($duplicates = [], $arrayData, $option = [])
    {
        $exception = isset($option['exceptionVal']) ? $option['exceptionVal'] : []; # Default exception is null array
        $settKey = 0; # Default is unset by last value
        /* Clean array */
        foreach ($duplicates as $key => $duplicateVal) {
            if (!in_array($duplicateVal, $exception)) { # Set optional exception unset by value
                if (count($arrayData) > 1) {
                    $keys = array_keys($arrayData, $duplicateVal);
                    if (isset($option['custom'])) { # Optional unset by value and by last or first sequence
                        foreach ($option['custom'] as $keyCustom => $optCustom) {
                            if (count($optCustom) > 1) {
                                $settKey = $optCustom[array_key_last($optCustom)];
                            }

                            if ($optCustom[array_key_first($optCustom)] == $duplicateVal) {
                                unset($arrayData[$keys[$settKey]]); # 0 Small key
                            }
                        }
                    } else {
                        /* Default is unset by last value */
                        unset($arrayData[$keys[0]]);
                    }

                    $counts = $this->get_StructureDuplicate($arrayData);
                    if (count($counts) == 1) {
                        $arrayData = $this->clean_StructureDuplicate($counts, $arrayData, $option);
                    }
                }
            }
        }
        return $arrayData;
    }
}
