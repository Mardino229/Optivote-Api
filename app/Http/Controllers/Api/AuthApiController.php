<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PasswordReset;
use App\Models\Person;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthApiController extends Controller
{

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
        $request->validate([
            "email" => "required|email",
            "npi" => "required|integer|exists:persons,npi",
            "password" => "required|min:8",
        ]);

        $person = Person::where('npi', $request->npi)->first();

        if ($person == null) {
            return ResponseApiController::apiResponse(false, 'Npi incorrect.', '', 404);
        }

        $age = Carbon::parse($person->birthday)->age;

        if ($age < 18) {
            return ResponseApiController::apiResponse(false, 'Vous devez avoir au moins 18ans pour créer un compte', '', 404);
        }

        $user = User::where('npi', $request->npi)->first();

        if ($user == null) {
            $user = User::where('email', $request->email)->first();
            if ($user == null) {
                $user = User::create([
                    'name' => $person->name,
                    'email' => $request->email,
                    'birthday' => $person->birthday,
                    'npi' => $request->npi,
                    'password' => Hash::make($request->password),
                ]);
                $user->assignRole('user');
                return ResponseApiController::apiResponse(true, 'Compte créer avec succès', '', 201);
            }
        }

        return ResponseApiController::apiResponse(false, "L'utilisateur existe déjà", '', 406);
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
     *   "body": {
     *      "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9",
     *      "id": 2,
     *      "role": "admin",
     *      "npi": 1952368744,
     *   }
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
            return ResponseApiController::apiResponse(true, 'Connexion réussie', [
                'accessToken' => $token->plainTextToken,
                'id' => $user->id,
                'role' => $user->roles[0]->name,
                'npi' => $user->npi,
            ]);
        }
        return ResponseApiController::apiResponse(false, 'Identifiants incorrects', '', 404);
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

        return ResponseApiController::apiResponse(true, 'Déconnexion réussie', '', 201);
    }

    /**
     * Send reset password Otp.
     *
     * This endpoint send reset password Otp to user email.
     *
     * @group Authentication
     * @bodyParam email string required The email address of the user. Example: user@example.com
     * @response 200 {
     *   "success": true,
     *   "message": "OTP de réinitialisation de mot de passe envoyé avec succès",
     *   "body": null
     * }
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $email = $request->email;

        // Générer un OTP
        $otp = rand(100000, 999999);

        // Stocker l'OTP
        PasswordReset::updateOrCreate(
            ['email' => $email],
            [
                'otp' => $otp,
                'expires_at' => Carbon::now()->addMinutes(10), // OTP valable pendant 10 minutes
            ]
        );

        // Envoyer l'OTP par email
        Mail::raw("Votre code OTP de réinitialisation de mot de passe est : $otp. Ce code expire dans 10 minutes.", function ($message) use ($email) {
            $message->to($email)
                ->subject('Réinitialisation de mot de passe');
        });

        return ResponseApiController::apiResponse(true, "OTP de réinitialisation de mot de passe envoyé avec succès");
    }

    /**
     * Reset password.
     *
     * This endpoint reset user password.
     *
     * @group Authentication
     * @bodyParam email string required The email address of the user. Example: user@example.com
     * @bodyParam otp numeric required
     * @bodyParam password string required minimum six characters
     * @bodyParam password_confirmation string required equal to password
     * @response 200 {
     *   "success": true,
     *   "message": "Mot de passe réinitialisé avec succès",
     *   "body": null
     * }
     * @response 400 {
     *   "success": false,
     *   "message": "OTP invalide ou expiré.",
     *   "body": null
     * }
     * @response 400 {
     *   "success": false,
     *   "message": "OTP expiré.",
     *   "body": null
     * }
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $passwordReset = PasswordReset::where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        if (!$passwordReset) {
            return ResponseApiController::apiResponse(false, 'OTP invalide ou expiré.', 400);
        }

        // Vérifier si l'OTP a expiré
        if (Carbon::now()->greaterThan($passwordReset->expires_at)) {
            return ResponseApiController::apiResponse(false,'OTP expiré.', [], 400);
        }

        // Réinitialiser le mot de passe
        $user = User::where('email', $request->email)->first();
        $user->password = bcrypt($request->password);
        $user->save();

        // Supprimer l'OTP
        $passwordReset->delete();

        return ResponseApiController::apiResponse(true,'Mot de passe réinitialisé avec succès');
    }

}
