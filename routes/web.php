<?php

use App\Http\Controllers\Api\PersonController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PersonController::class, "index"])->name("person");


Route::post('/create', [PersonController::class, "store"])->name("store");


Route::get('/create', [PersonController::class, "create"])->name("create");
