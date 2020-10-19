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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('test', function (){
    return var_dump(\request()->route()->getPrefix());
});

Route::group([
    'middleware'    => 'api',
    'namespace'     => 'Api\Auth'
], function (){
    Route::post('login', 'LoginController@login');
    Route::post('logout', 'LoginController@logout');
    Route::post('refresh', 'LoginController@refresh');
    Route::post('me', 'LoginController@me');
    Route::post('register', 'RegisterController@register');
});

Route::group(['middleware' => 'api', 'namespace' => 'Api'], function () {
    // Users
    Route::get('/{slug}/users',['as' => 'users.index','uses' =>'UserController@index']);
    Route::put('/{slug}/users/invite',['as' => 'users.invite.update','uses' =>'UserController@inviteUser']);
    Route::put('/{slug}/users/update/{id}',['as' => 'users.update','uses' =>'UserController@update']);
    Route::delete('/{slug}/users/{id}',['as' => 'users.remove','uses' =>'UserController@removeUser']);

    Route::post('/my-account/password', ['as' => 'update.password','uses' =>'UserController@updatePassword']);

    // Projects
    Route::get('/{slug}/projects',['as' => 'projects.index', 'uses' => 'ProjectController@index']);
    Route::get('/{slug}/projects/{id}',['as' => 'projects.show','uses' =>'ProjectController@show']);
    Route::post('/{slug}/projects',['as' => 'projects.store','uses' =>'ProjectController@store']);
    Route::put('/{slug}/projects/{id}',['as' => 'projects.update','uses' =>'ProjectController@update']);
    Route::delete('/{slug}/projects/{id}',['as' => 'projects.destroy','uses' =>'ProjectController@destroy']);
//    Route::delete('/{slug}/projects/leave/{id}',['as' => 'projects.leave','uses' =>'ProjectController@leave'])->middleware(['auth','XSS']);
//    Route::get('/{slug}/projects/invite/{id}',['as' => 'projects.invite.popup','uses' =>'ProjectController@popup'])->middleware(['auth','XSS']);
//    Route::get('/{slug}/projects/share/{id}',['as' => 'projects.share.popup','uses' =>'ProjectController@sharePopup'])->middleware(['auth','XSS']);
//    Route::post('/{slug}/projects/share/{id}',['as' => 'projects.share','uses' =>'ProjectController@share'])->middleware(['auth','XSS']);
//    Route::put('/{slug}/projects/invite/{id}',['as' => 'projects.invite.update','uses' =>'ProjectController@invite'])->middleware(['auth','XSS']);
//    Route::get('/{slug}/projects/milestone/{id}',['as' => 'projects.milestone','uses' =>'ProjectController@milestone'])->middleware(['auth','XSS']);
//    Route::post('/{slug}/projects/milestone/{id}',['as' => 'projects.milestone.store','uses' =>'ProjectController@milestoneStore'])->middleware(['auth','XSS']);
//    Route::get('/{slug}/projects/milestone/{id}/show',['as' => 'projects.milestone.show','uses' =>'ProjectController@milestoneShow'])->middleware(['auth','XSS']);
//    Route::get('/{slug}/projects/milestone/{id}/edit',['as' => 'projects.milestone.edit','uses' =>'ProjectController@milestoneEdit'])->middleware(['auth','XSS']);
//    Route::put('/{slug}/projects/milestone/{id}',['as' => 'projects.milestone.update','uses' =>'ProjectController@milestoneUpdate'])->middleware(['auth','XSS']);
//    Route::delete('/{slug}/projects/milestone/{id}',['as' => 'projects.milestone.destroy','uses' =>'ProjectController@milestoneDestroy'])->middleware(['auth','XSS']);
//    Route::post('/{slug}/projects/{id}/file',['as' => 'projects.file.upload','uses' =>'ProjectController@fileUpload'])->middleware(['auth','XSS']);
//    Route::get('/{slug}/projects/{id}/file/{fid}',['as' => 'projects.file.download','uses' =>'ProjectController@fileDownload'])->middleware(['auth','XSS']);
//    Route::delete('/{slug}/projects/{id}/file/delete/{fid}',['as' => 'projects.file.delete','uses' =>'ProjectController@fileDelete'])->middleware(['auth','XSS']);

});
