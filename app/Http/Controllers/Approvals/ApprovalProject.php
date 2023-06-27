<?php

namespace App\Http\Controllers\Approvals;

use App\Http\Controllers\Controller;
use App\Models\Master\Company;
use App\Models\Master\Departement;
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
        ])
            ->firstOrFail();

        // ->orderBy('dt_structural.STRUCTURE_NUMBER', 'desc')
        // $_company->COMP_ID;

        return response($mStructure);
    }

    private function setApproval(Request $request, string $uuid)
    {
    }
}
