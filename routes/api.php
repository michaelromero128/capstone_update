<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

//Route::middleware('auth:api')->get('/user', function (Request $request) {
 //   return $request->user();
//});



Route::namespace('Api')->group(function () {
    Route::post('/password-grant-auth','AuthController@grant' )->middleware('throttle:60,1')->name('auth_grant');
    Route::post('/refresh','AuthController@refresh' )->middleware('throttle:60,1')->name('auth_refresh');
    
    Route::get('/events','EventController@index')->name('event.index');
    Route::post('/events', 'EventController@store')->name('event.store')->middleware('auth:api');
    Route::get('/events/{event}', 'EventController@show')->name('event.show');
    Route::put('/events/{event}', 'EventController@update')->middleware('auth:api')->name('event.update');
    Route::delete('/events/{id}', 'EventController@destroy')->middleware('auth:api')->name('event.destroy');
    
    Route::post('/file/{id}', 'EventPhotoController@fileAdd')->middleware('auth:api')->name('file.add');
    Route::delete('/file/{id}', 'EventPhotoController@fileRemove')->middleware('auth:api')->name('file.delete');
    Route::get('/file/{id}', 'EventPhotoController@fileGet')->name('file.get');
    
   
    
    Route::post('user/change','UserController@changeRank')->middleware('auth:api')->name('user.change_rank');
    Route::get('user/profile/{id}', 'UserController@getProfile')->name('user.profile');

    
});

