<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\CandidatController;
use App\Http\Controllers\Api\ElectionController;
use App\Http\Controllers\Api\ResultatController;
use App\Http\Controllers\Api\VoteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::controller(AuthApiController::class)->group(function(){
    Route::post('register', 'create');
    Route::post('login', 'login');
    Route::post('logout', 'destroy');
    Route::post('/password/send-otp', 'sendOtp');
    Route::post('/password/reset', 'resetPassword');
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::resource('elections', ElectionController::class)->middleware('role:admin');

    Route::controller(ElectionController::class)->group(function(){
        Route::get('election_inprogress', 'election_inprogress');
        Route::get('election_completed', 'election_completed');
        Route::get('election_notStarted', 'election_notStarted');
        Route::post('second_tour/{election_id}', 'second')->middleware('role:admin');
    });

    Route::controller(CandidatController::class)->group(function(){
        Route::get('candidats/{election_id}', 'index');
        Route::get('candidat/{id}', 'show');
        Route::post('candidat/{id}', 'update');
        Route::post('candidat', 'store');
        Route::delete('candidat/{id}', 'destroy');
    });

    Route::controller(VoteController::class)->group(function(){
        Route::get('votes/{election_id}', 'index');
        Route::get('vote/{election_id}/{user_id}', 'verifyVote');
        Route::post('vote', 'store');
//        Route::put('vote/{id}', 'update');
//        Route::delete('vote/{id}', 'destroy');
    });

    Route::controller(ResultatController::class)->group(function(){
        Route::get('resultats/{election_id}', 'index');
    });

});
