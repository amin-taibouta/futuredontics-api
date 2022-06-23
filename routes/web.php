<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

Route::get('/', function(Request $request) {
    //Verify the accessToken
    $accessToken = $request->get('accessToken');
    if (!in_array($accessToken, config('auth')['accessTokens'])) {
        return response()->json([
            'error' =>  'Invalid access token.',
            'accessToken' => $accessToken
        ]);
    }

    return response()->json([
        'futuredontics-api'
    ]);
});

Route::get('/ping', function(Request $request) {
    //log the request
    Log::info("API Request", $request->all());
    //Verify the accessToken
    $accessToken = $request->get('accessToken');
    if (!in_array($accessToken, config('auth')['accessTokens'])) {
        //log the request
        Log::info("Invalid access token.", [$accessToken]);
        return response()->json([
            'error' =>  'Invalid access token.',
            'accessToken' => $accessToken
        ]);
    }

    /*
        Target Sub ID (CID)
        Billable Duration
        Revenue Amount
        Publisher Payout Bid
        Publisher Duration Bid
        PostbackURL
    */
    /*$zipCode = $request->get('zipcode');
    Log::info("Invalid access token.", [$accessToken]);
        return response()->json([
            'error' =>  'Invalid or empty zipcode.',
            'accessToken' => $accessToken
        ]);*/
    //fetch date
    $return = DB::select(
        DB::raw("SET NOCOUNT ON; exec dbo.DTP_ProcessCall @zipcode = '91324'")
    );
    //log the request
    Log::info("API Response", [$return]);
    //return the data
    return response()->json([
        'response' => $return
    ]);
});