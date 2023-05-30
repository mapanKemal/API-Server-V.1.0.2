<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Master\EmployeePosition;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
use Ramsey\Uuid\Uuid;

class UserAuthentication extends Controller
{
    /**
     *  Register User
     *  @param Request $request
     *  @return User
     */
    public function register(Request $request)
    {
        try {
            // Validation
            $validateUser = Validator::make(
                $request->all(),
                [
                    'identity' => ['required', Rule::unique('ms_users', 'USERNAME'), 'min:2']
                ]
            );

            if ($validateUser->fails()) {
                return response([
                    'status' => false,
                    'message' => 'User Validation Error',
                    'errors' => $validateUser->errors()
                ], 401);
            }

            $user = User::create([
                'ALIASES' => $request->alias,
                'USERNAME' => $request->identity,
                'PASSWORD' => Hash::make($request->identity)
            ]);

            $userToken = $user->createToken('authToken');
            $tokenId = $userToken->token->id;
            $accessToken = $userToken->accessToken;

            return response()->json([
                'status' => true,
                'message' => 'User Created Successfully',
                'user'  => [
                    'USER_ID' => $user->UUID,
                    'USER_ALIASES' => $user->ALIASES
                ],
                'access_token' => $accessToken,
                'token_id' => $tokenId,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     *  Login User
     *  @param Request $request
     *  @return User
     */
    public function login(Request $request)
    {
        // 'username' => 'required',
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'identity' => 'required'
                ]
            );

            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'User Validation Error',
                    'errors' => $validateUser->errors()
                ], 401);
            }

            if (!Auth::attempt(['username' => $request->identity, 'password' => $request->identity])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Username & Password does not match with our record.',
                ], 401);
            }

            $result = [];
            $user = User::select(
                'ms_users.USER_ID',
                'ms_users.UUID',
                'ms_users.ALIASES',
                'ms_users.LAST_LOGIN_AT',
                'ms_employee.EMPL_ID',
                'ms_employee.EMPL_NUMBER',
                'ms_employee.EMPL_UNIQUE_CODE',
                'ms_employee.EMPL_FIRSTNAME',
                'ms_employee.EMPL_LASTNAME',
                'ms_employee.EMPL_GENDER',
                'ms_employee.STATUS',
                'ms_employee.EMPL_CONFIG',
            )
                ->leftJoin('ms_employee', 'ms_employee.USER_ID', '=', 'ms_users.USER_ID')
                ->where([['ms_users.USERNAME', $request->identity], ['ms_users.STATUS', 'in', [100, 0]]])
                ->first();

            /* Last Login Update */
            if (is_null($user)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Employee not found or not active, please contact Administrator',
                ], 400);
            }
            $user->LAST_LOGIN_AT = $request->lastLogin == null ? now() : $request->lastLogin;
            $user->save();
            /* Set User Data */
            $result = [
                "UUID" => $user->UUID,
                "ALIASES" => $user->ALIASES,
                "EMPL_ID" => $user->EMPL_ID,
                "EMPL_NUMBER" => $user->EMPL_NUMBER,
                "EMPL_UNIQUE_CODE" => $user->EMPL_UNIQUE_CODE,
                "EMPL_FIRSTNAME" => $user->EMPL_FIRSTNAME,
                "EMPL_LASTNAME" => $user->EMPL_LASTNAME,
                "EMPL_GENDER" => $user->EMPL_GENDER,
                "EMPL_DEFAULT_CMP" => null,
                "EMPL_DEFAULT_DPT" => null,
                "STATUS" => $user->STATUS,
                "EMPL_CONFIG" => $user->EMPL_CONFIG,
                "LAST_LOGIN_AT" => $user->LAST_LOGIN_AT,
            ];
            /* Set User Structure */
            $userStructure_dep = EmployeePosition::select(
                "fr_employee_position.FR_POST_ID",
                "fr_employee_position.COMP_ID",
                "CMP.COMP_CODE",
                "CMP.COMP_NAME",
                "fr_employee_position.DEPT_ID",
                "DPT.DEPT_CODE",
                "DPT.DEPT_NAME",
            )
                ->where("EMPL_ID", $user->EMPL_ID)
                ->join('ms_departement as DPT', 'DPT.DEPT_ID', '=', 'fr_employee_position.DEPT_ID')
                ->join('ms_company as CMP', 'CMP.COMP_ID', '=', 'fr_employee_position.COMP_ID')
                ->get();
            $result['EMP_STRUCTURE'] = $userStructure_dep;

            $userToken = $user->createToken('authToken');
            $tokenId = $userToken->token->id;
            $accessToken = $userToken->accessToken;
            /* And Then */
            return response()->json([
                'status' => true,
                'message' => 'User Logged In Successfully',
                'token_id' => $tokenId,
                'access_token' => $accessToken,
                'user'  => $result
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return response(User::all());
    }

    /**
     * When cannot found user token
     */
    public function AuthenticationNeeded()
    {
        //
        return response(['message' => 'Unauthenticated.'], 401);
    }

    /**
     * Logout and revoke token
     */
    public function logout(string $tokensId)
    {
        Token::where('id', $tokensId)
            ->update(['revoked' => true]);
        RefreshToken::where('access_token_id', $tokensId)->update(['revoked' => true]);
        return response('Logout Success', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
