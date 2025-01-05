<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthApiController extends Controller
{
    public static function apiResponse($success, $message, $data = [], $status = 200)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'body' => $data
        ], $status);
    }

    /**
     * Create a new user account.
     *
     * This endpoint allows users to create a new account.
     * The user must provide their NPI, email, and password.
     *
     * @group Authentication
     * @bodyParam npi integer required The unique NPI identifier of the person. Example: 123456789
     * @bodyParam email string required The email address of the user. Example: user@example.com
     * @bodyParam password string required The password for the new account. Minimum length: 8 characters.
     * @response 201 {
     *   "success": true,
     *   "message": "Compte créer avec succès",
     *   "body": ""
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Npi incorrect.",
     *   "body": ""
     * }
     * @response 406 {
     *   "success": false,
     *   "message": "L'utilisateur existe déjà",
     *   "body": ""
     * }
     */
    public function create(Request $request)
    {
        $person = Person::where('npi', $request->npi)->first();

        if ($person == null) {
            return self::apiResponse(false, 'Npi incorrect.', '', 404);
        }

        $age = Carbon::parse($person->birthday)->age;

        if ($age < 18) {
            return self::apiResponse(false, 'Vous devez avoir au moins 18ans pour créer un compte', '', 404);
        }

        $user = User::where('npi', $request->npi)->first();

        if ($user == null) {
            $user = User::where('email', $request->email)->first();
            if ($user == null) {
                $request->validate([
                    "email" => "required|email",
                    "npi" => "required|integer",
                    "password" => "required|min:8",
                ]);
                $user = User::create([
                    'name' => $person->name,
                    'email' => $request->email,
                    'birthday' => $person->birthday,
                    'npi' => $request->npi,
                    'password' => Hash::make($request->password),
                ]);
                $user->assignRole('user');
                return self::apiResponse(true, 'Compte créer avec succès', '', 201);
            }
        }

        return self::apiResponse(false, "L'utilisateur existe déjà", '', 406);
    }

    /**
     * Log in a user.
     *
     * This endpoint allows a user to log in with their email and password.
     *
     * @group Authentication
     * @bodyParam email string required The email address of the user. Example: user@example.com
     * @bodyParam password string required The password of the user.
     * @response 200 {
     *   "success": true,
     *   "message": "Connexion réussie",
     *   "body": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
     * }
     * @response 401 {
     *   "success": false,
     *   "message": "Identifiants incorrects",
     *   "body": ""
     * }
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $user = User::find(Auth::user()->id);
            $token = $user->createToken($request->email);
            return self::apiResponse(true, 'Connexion réussie', $token->plainTextToken);
        }
        return self::apiResponse(false, 'Identifiants incorrects', '');
    }

    /**
     * Log out a user.
     *
     * This endpoint logs out the authenticated user and deletes their tokens.
     *
     * @group Authentication
     * @bodyParam npi integer required The NPI of the user. Example: 115586654
     * @response 201 {
     *   "success": true,
     *   "message": "Déconnexion réussie",
     *   "body": ""
     * }
     */
    public function destroy(Request $request)
    {
        Auth::logout();
        $user = User::where('npi', $request->npi)->first();
        $user->tokens()->delete();

        return self::apiResponse(true, 'Déconnexion réussie', '', 201);
    }
}
