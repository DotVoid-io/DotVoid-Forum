<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

// TODO put in "can" middleware
Route::resource('categories', 'CategoryController', ['except' => ['create','edit']]);
Route::get('categories/{category}/threads', 'CategoryController@threads')->name('categories.threads');