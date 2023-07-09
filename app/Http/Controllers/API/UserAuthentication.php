<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Master\EmployeePosition;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
use Spatie\Activitylog\Models\Activity;

class UserAuthentication extends Controller
{
    public function tester()
    {
    }
    /**
     *  Register User
     *  @param Request $request
     *  @return User
     */
    public function register(Request $request)
    {
        // Validation
        $validateUser = Validator::make(
            $request->all(),
            [
                'username' => ['required', Rule::unique('ms_users', 'USERNAME'), 'min:2'],
                'password' => ['required', 'min:3'],
                // 'identity' => ['required', Rule::unique('ms_users', 'USERNAME'), 'min:2']
            ]
        );

        if ($validateUser->fails()) {
            return response([
                'status' => false,
                'message' => 'Input Validation Error',
                'errors' => $validateUser->errors()
            ], 401);
        }

        try {

            $user = User::create([
                'ALIASES' => $request->alias,
                'USERNAME' => $request->username,
                'PASSWORD' => Hash::make($request->password)
                // 'USERNAME' => $request->identity,
                // 'PASSWORD' => Hash::make($request->identity)
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
        $result = [];
        $validateUser = Validator::make(
            $request->all(),
            [
                'username' => 'required',
                'password' => 'required',
                // 'identity' => 'required',
            ]
        );

        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Input Validation Error',
                'errors' => $validateUser->errors()
            ], 401);
        }


        if (!Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
            return response()->json([
                'status' => false,
                'message' => 'Username & Password does not match with our record.',
            ], 401);
        }

        $user = User::select(
            'ms_users.USER_ID',
            'ms_users.UUID',
            'ms_users.ALIASES',
            'ms_users.LAST_LOGIN_AT',
            'ms_users.PASSWORD_VERIFIED_AT',
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
            ->where([['ms_users.USERNAME', $request->username]])
            ->whereIn('ms_users.STATUS', [0, 100])
            ->first();
        try {

            if (is_null($user->PASSWORD_VERIFIED_AT)) {
                return response([
                    'status' => false,
                    'message' => 'Password not verified',
                ], 418);
            }
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
                "ALIASES" => ucfirst($user->ALIASES),
                "USER_ID" => $user->USER_ID,
                "EMPL_ID" => $user->EMPL_ID,
                "EMPL_NUMBER" => $user->EMPL_NUMBER,
                "EMPL_UNIQUE_CODE" => $user->EMPL_UNIQUE_CODE,
                "EMPL_FIRSTNAME" => ucfirst($user->EMPL_FIRSTNAME),
                "EMPL_LASTNAME" => ucfirst($user->EMPL_LASTNAME),
                "EMPL_GENDER" => $user->EMPL_GENDER,
                "EMPL_DEFAULT_CMP" => null,
                "EMPL_DEFAULT_DPT" => null,
                "STATUS" => $user->STATUS,
                "EMPL_CONFIG" => $user->EMPL_CONFIG,
                "LAST_LOGIN_AT" => $user->LAST_LOGIN_AT,
            ];
            /* Set User Structure */
            $userStructure_dep = EmployeePosition::select(
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
                ->groupBy('fr_employee_position.COMP_ID', 'fr_employee_position.DEPT_ID')
                ->get();
            $result['EMP_STRUCTURE'] = $userStructure_dep;

            $userToken = $user->createToken('authToken');
            $tokenId = $userToken->token->id;
            $accessToken = $userToken->accessToken;

            /* Log */
            activity('Authentication')
                ->causedBy(Auth::user())
                ->event('Login')
                ->log('Login succesfull');
            /* And Then */
            return response()->json([
                'status' => true,
                'message' => 'User Logged In Successfull',
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

    public function create_newPassword(Request $request)
    {
        $validateUser = Validator::make(
            $request->all(),
            [
                'username' => ['required'],
                'oldPassword' => ['required'],
                'newPassword' => ['required', 'different:oldPassword'],
                'passwordConfirmed' => ['required'],
            ],
            [
                'newPassword.different' => 'The new password must be different.'
            ]
        );

        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Input Validation Error',
                'errors' => $validateUser->errors()
            ], 418);
        }

        try {
            /* Update new password */
            DB::transaction(function () use ($request) {
                $setNewPassword = User::where('USERNAME', $request->username)
                    ->firstOrFail();
                $setNewPassword->PASSWORD = Hash::make($request->newPassword);
                $setNewPassword->PASSWORD_VERIFIED_AT = date('Y-m-d H:i:s');
                $setNewPassword->save();
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

        try {
            DB::transaction(function () use ($tokensId) {
                activity('Authentication')
                    ->causedBy(Auth::user())
                    ->event('Logout')
                    ->log('Logout account and revoke token');
                Token::where('id', $tokensId)
                    ->update(['revoked' => true]);
                RefreshToken::where('access_token_id', $tokensId)->update(['revoked' => true]);
            });
            DB::commit();
            return response('Logout Success', 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            /* Return Response on error */
            return response([
                "error" => $e->getMessage(),
                "message" => "Sorry, System can't receive your request",
            ], 500);
        }
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
