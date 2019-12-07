<?php

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Route::get('/', function () {
    //return view('welcome');
//    return response()->json(['error' => "This is a api, don't come here"], 400);

//});

Auth::routes(['verify' => true]);
Route::post('email/customVerify', 'Auth\VerificationController@customVerify')->middleware('throttle:60,1')->name('email.customVerify');
Route::post('email/customResend', 'Auth\VerificationController@customResend')->middleware('throttle:60,1')->name('email.customResend');
Route::post('password/change', 'Auth\ChangePasswordController@changePassword')->middleware('auth:api','throttle:60,1')->name('password.change');
//Route::get('/home', 'HomeController@index')->name('home')->middleware('verified');
