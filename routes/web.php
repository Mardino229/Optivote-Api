<?php

use App\Http\Controllers\Api\PersonController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PersonController::class, "create"])->name("create");


Route::post('/create', [PersonController::class, "store"])->name("store");


//Route::get('/create', [PersonController::class, "create"])->name("create");
